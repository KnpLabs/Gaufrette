v0.8.1
======

## Fixes

- Fix `rename` with `GoogleCloudStorage` adapter [#598](https://github.com/KnpLabs/Gaufrette/pull/598)

Thank you @jerome-arzel for your contribution !

v0.8
====

## New features

- Implement `ChecksumCalculator` interface for `AzureBlobStorage` adapter #594

## Changes

In #593 :
- Drop support for EOL php versions (5.6 and 7.0)
- Minimim requirement is now php 7.1
- Add support for php 7.3

## Fixes

- fix opencloud tests #579
- fix appveyor build #589
- Fix `ini_get()` for boolean values #595

Thank you @andreybolonin, @damijank, @deguif and @nicolasmure for your
contributions !

v0.7
====

## Changes
- `FilesystemMap::set()` should expect `FilesystemInterface` instead of
`Filesystem` #576

## Fixes

- Add PutObjectAcl in the required permission #566
- Ensure correct return type from Flysystem adapter "exists" method #572

Thank you @andreybolonin, @clement-michelet, @jakob-stoeck, @nicolasmure,
@teohhanhui, @tristanbes for your contributions !

v0.6
====

## Changes
- Add support for major release of Azure Blob Storage SDK [#558](https://github.com/KnpLabs/Gaufrette/pull/558)

## Fixes
- Fix Dockerfile for php 7.0 [aaa66dc](https://github.com/KnpLabs/Gaufrette/commit/aaa66dcf298d313e7ae3f525714923fcfd787e94)
- Fix appveyor build [#562](https://github.com/KnpLabs/Gaufrette/pull/562)

Thank you @nicolasmure, @NiR- and @z38 for your contributions!

v0.5
====

## New features
- Added support for calculated size for Azure Blob Storage #523
- GridFS Support for Metadata Retrieval after Write #535

## Changes
- Test case for AwsS3 now inherits common test case #514
- Run azure tests on appveyor #512
- Bump PHPUnit to ^5.6.8 #529
- Use composer's autoload-dev #530
- Drop HHVM support + sync docker conf with Travis #528
- Refactoring tests to have more detailed failure messages #542

## Fixes
- Documentation #510
- Typos #506, #538
- Fix incomplete tear down phase for AwsS3Test #516
- Fix FTP tests + bug in PhpseclibSftp::fetchKeys() #527
- fix travis build for php 5.6 #543
- Quickfix for Adapter/AwsS3, check if count() call is allowed #544

Thank you @andreasschacht, @bsperduto, @carusogabriel, @dawkaa, @gerkestam2,
@GrahamCampbell, @Lctrs, @nicolasmure, @NiR- for your contributions !

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
