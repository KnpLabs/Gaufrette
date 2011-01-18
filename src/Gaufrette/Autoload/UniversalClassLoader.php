<?php

namespace Gaufrette\Autoload;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class UniversalClassLoader
{
    protected $namespaces = array();
    protected $prefixes = array();

    public function getNamespaces()
    {
        return $this->namespaces;
    }

    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * Registers an array of namespaces
     *
     * @param array $namespaces An array of namespaces (namespaces as keys and locations as values)
     */
    public function registerNamespaces(array $namespaces)
    {
        $this->namespaces = array_merge($this->namespaces, $namespaces);
    }

    /**
     * Registers a namespace.
     *
     * @param string $namespace The namespace
     * @param string $path      The location of the namespace
     */
    public function registerNamespace($namespace, $path)
    {
        $this->namespaces[$namespace] = $path;
    }

    /**
     * Registers an array of classes using the PEAR naming convention.
     *
     * @param array $classes An array of classes (prefixes as keys and locations as values)
     */
    public function registerPrefixes(array $classes)
    {
        $this->prefixes = array_merge($this->prefixes, $classes);
    }

    /**
     * Registers a set of classes using the PEAR naming convention.
     *
     * @param string $prefix The classes prefix
     * @param string $path   The location of the classes
     */
    public function registerPrefix($prefix, $path)
    {
        $this->prefixes[$prefix] = $path;
    }

    /**
     * Registers this instance as an autoloader.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     */
    public function loadClass($class)
    {
        if ('\\' === $class[0]) {
            $class = substr($class, 1);
        }

        if (false !== ($pos = strripos($class, '\\'))) {
            // namespaced class name
            $namespace = substr($class, 0, $pos);
            foreach ($this->namespaces as $ns => $dir) {
                if (0 === strpos($namespace, $ns)) {
                    $class = substr($class, $pos + 1);
                    $file = $dir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
                    if (file_exists($file)) {
                        require $file;
                    }

                    return;
                }
            }
        } else {
            // PEAR-like class name
            foreach ($this->prefixes as $prefix => $dir) {
                if (0 === strpos($class, $prefix)) {
                    $file = $dir.DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
                    if (file_exists($file)) {
                        require $file;
                    }

                    return;
                }
            }
        }
    }
}
