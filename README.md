# Tor detector

A very simple library to detect Tor connections using PHP 7 without any dependency.

# Usage

Before using the library, you have to set the path to the file that contains the list of all Tor exit points, if you don't have the list you can set an arbitrary file name instead.
To set the library path, use this method:

`PHPTorDetector\PHPTorDetector::setListPath('nodes.txt');`

If the file doesn't exist or is empty or you just want to update its content, you can use this method to download the updated list and overwrite new list in the file:

`PHPTorDetector\PHPTorDetector::updateFile();`

Once you have the list, you can check if an IP address is part of the Tor network by using this method:

`$result = PHPTorDetector\PHPTorDetector::isTor('IP ADDRESS HERE');`

If you want to get the IP address of the client you can use this method:

`$address = PHPTorDetector\PHPTorDetector::getClientIPAddress();`

By default, check result is cached within session (if session is enabled and if the script is not executed in CLI), you can disable this feature by using this method:

`PHPTorDetector\PHPTorDetector::setSessionCache(false);`

If you want to change the index where the results will be cached you can use this method:

`PHPTorDetector\PHPTorDetector::setSessionCacheName('tor_cache');`

Note that if you want to set multiple indexes within the session array you can separate indexes with a "@" like this:

`PHPTorDetector\PHPTorDetector::setSessionCacheName('tor@cache');`

In this way, cache will be stored in `$_SESSION['tor']['cache']`.

Are you looking for the Node.js version? Give a look [here](https://github.com/RyanJ93/tor-detector).