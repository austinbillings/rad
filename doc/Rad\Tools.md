# Rad\\Tools

## Overview

The `Rad\Tools` class is a utility class for Rad that provides a whole mess of useful utility functions, which are used elsewhere in the Rad core code (and which you may find useful).

These functions are all static class methods, so you don't need to instantiate the `Rad\Tools` class in order to use them. Simply `use` the class like so:

```php
<?php

namespace MyApp
use \Rad\Tools  
// now the 'Tools' class refers to the Rad Tools.

class MyClass extends \Rad\Base
{
  // Do yo thang, homie
}
```

# The Tools

Without further adieu, use these jawns.


---
## Names

### `Tools::firstName(string $input)`

**Extracts a first name.**

|Parameter|Type|Description|
|:--|:--|:--|
|`$input`|String|The full name you want a first name extracted from.

Returns a `string` best guess at a first name (or given name) given an expected full name. Actually just explodes the string by spaces and returns the first segment.

#### Example

```php
Tools::firstName("Argloy Joyseif Bilnog");

// returns "Argloy";
```


### `Tools::lastName(string $input)`

**Extracts a last name / surname.**

|Parameter|Type|Description|
|:--|:--|:--|
|`$input`|String|The full name you want a last name extracted from.

Returns a `string` best guess at a last name (or surname) given an expected full name. Actually just explodes the string by spaces and returns the last segment.

#### Example

```php
Tools::firstName("Argloy Joyseif Bilnog");

// returns "Bilnog";
```

---

## Paths, URIs, and URLs

### `Tools::getSiteURL(void)`

**Get the the current domain, with protocol and port number.**

*This function accepts no parameters.*

Returns the `string` URL of the current host, including protocol and port number (if other than 80). Uses `$_SERVER['SERVER_PORT']` for port number, which can be spoofed, so *do not rely on this function in a security-dependent context*.

#### Example

```php
Tools::getSiteURL();

// returns "http://localhost:1234";
// or maybe something like "https://mydomain.com"
```

### `Tools::getFullURL(void)`


**Get the the current requested URI, including domain, protocol, and port number.**

*This function accepts no parameters.*

Returns the `string` URL of the request, including protocol and port number (if other than 80), and the request URI (not including query string). Uses `$_SERVER['SERVER_PORT']` for port number, which can be spoofed, so *do not rely on this function in a security-dependent context*.

#### Example

```php
Tools::getFullURL();

// returns "http://localhost:1234/api/books/1234";
```

### `Tools::containsPeriod(string $input)`

**Check whether or not a string contains a period.**

|Parameter|Type|Description|
|:--|:--|:--|
|`$input`|String|The string you want to check for a period

Returns a `boolean` indicating the presence of a period in the string given by `$input`.

#### Example
```php
Tools::containsPeriod('/path/to/a/directory');
// returns false

Tools::containsPeriod('/path/to/my/file.jpg');
// returns true
```


### `Tools::getFileExtension(string $input)`

**Gets the extension portion of a file's URI.**

|Parameter|Type|Description|
|:--|:--|:--|
|`$input`|String|A file name, path, or URI

- Returns a `string` of the (final) file extension of the name/path provided by `$input`.
- Returns `false` if `$input` doesn't contain a period.

Actually returns the segment of the given string following the last period present. The returned extension is always lowercase and doesn't include the period preceding the file extension.

#### Example
```php
Tools::getFileExtension('/images/photo.jpg');
// returns 'jpg'

Tools::getFileExtension('/path/to/my/file.xml');
// returns 'xml'
```


### `Tools::stripFileExtension(string $input)`

**Strips the extension portion of a file's URI.**

*Alias: `Tools::removeFileExtension($input)`*

|Parameter|Type|Description|
|:--|:--|:--|
|`$input`|String|A file name, path, or URI

- Returns the given `string`, without the (final) file extension, if one exists.
- Returns `false` if `$input` doesn't contain a period.

Actually returns the segment of the given string before the last period present. The returned filename is always lowercase and doesn't include the period preceding the file extension.

#### Example
```php
Tools::stripFileExtension('/images/photo.jpg');
// returns '/images/photo'

Tools::stripFileExtension('/path/to/my/file.xml');
// returns '/path/to/my/file'
```


### `Tools::parseQuery(string $queryString)`

**Converts a query string to a pre-typed associative array.**

|Parameter|Type|Description
|:--|:--|:--
|`$queryString`|String|A regular ol' query string portion of a URL.|

 - Returns an associative array of the contents of given `$queryString`.
 - Returns `false` if `$queryString` isn't a string or is empty.

 This function performs an automatic type-casting of the given values (but leaves key names untouched).

 - Empty string values (e.g. the value of `myvar` in  `?myvar=&name=Steve`) are converted to `null`.
 - The values "true" and "false" (case-insensitive) are converted to actual `true` and `false` values, respectively.
 - Numeric values (i.e. those which pass PHP's stock `is_numeric()` test) are converted to `int` and `float` types (by adding 0 to their value, forcing PHP to intelligently identify integers and floats).
 - All other values (basically, all other strings) are URL-decoded.

#### Example
```php
$q = "?name=Argloy%20Bilnog&age=24&score=92.817&subscribe=false&email=";
Tools::parseQuery($q);

// Returns the following array (expressed here as JSON):

{
  "name": "Argloy Bilnog"
  "age": 24,
  "score": 92.817,
  "subscribe": false
  "email": null
}
```
