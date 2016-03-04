---
currentMenu: streaming
---

# Streaming

Sometimes, you don't have the choice, you must get a streamable file URL (i.e to use native file functions).
Let's take a look at the following example:

```php
$adapter = new InMemoryAdapter(array('hello.txt' => 'Hello World!'));
$filesystem = new Filesystem($adapter);

$map = StreamWrapper::getFilesystemMap();
$map->set('foo', $filesystem);

StreamWrapper::register();

copy('gaufrette://foo/hello.txt', 'gaufrette://foo/world.txt');
unlink('gaufrette://foo/hello.txt');

echo file_get_contents('gaufrette://foo/world.txt'); // Says "Hello World!"
```
