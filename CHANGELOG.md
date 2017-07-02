v0.4
====

* Following adapters have been deprecated: AclAwareAmazonS3, AmazonS3, Apc, Cache, LazyOpenCloud, Sftp, Dropbox, MogileFS, GoogleCloudStorage (see #482)
* Improvement of test coverage during CI builds: functional tests for AzureBlobStorage, AwsS3, DoctrineDbal, Ftp, GridFS, OpenCloud and PhpseclibSftp now run on Travis (see #457, #460, #483, #484, #500, #504, #505)
* Maintained adapters now have metapackage to enforce version of 3rd party libraries, and ease installation process (see #487)
* Add FilesystemInterface and make current Filesystem implement it (see #492)
* Drop support for PHP v5.4 and v5.5 (see #503)
* File:
  * Add rename method to File (see #468)
* Local adapter:
  * Suppress warning if directory has been created between check and create attempt (see #331)
  * Replace file_exists with is_file, to check if given path exists (see #479)
  * Allow Local adapter mkdir mode to cascade to it's Stream (see #488)
  * Fix phpdocs (see #489)
* AzureBlobStorage:
  * Add support for multi container mode (see #486)
* AwsS3 adapter:
  * Add ContentType support to AwsS3 (see #451)
  * Allow aws-sdk-php v2 and v3 to be used (see #457, #462, #475)
  * Provide mime type (see #491)
  * Deprecate AwsS3::getUrl() method, instead use ResolvableFilesystem from [`gaufrette/extras`](https://github.com/Gaufrette/extras) (see #496)
* GridFS adapter:
  * Unmaintained mongo extension has been replaced with newer mongodb extension (see #460)
* GoogleCloudStorage adapter:
  * Fixed missing leading "\" before Google_Http_Request (see #471)
* Ftp adapter:
  * Always ensure target directory exists before renaming (see #476)
  * Don't use FTP_USEPASSVADDRESS before php 5.6.18, and 7.0.0/7.0.1 (see #477, #480, #483)
* Docs:
  * Add minimum IAM roles for AwsS3 adapter, and recommend to manually create bucket (see #467)


Contributors: @NiR-, @nicolasmure, @WARrior-Alex, @zyphlar, @AntoineLelaisant, @Shivoham, @richardfullmer, @kcassam.

Also, we thank @edhgoose and @zyphlar who made patches for deprecated adapters, before those adapters were deprecated, but still did not see their respective work merged in this version.
