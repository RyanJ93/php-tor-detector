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

Are you looking for the Node.js version? Give a look [here](https://github.com/RyanJ93/tor-detector).