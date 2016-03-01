![EasyImage v2.7 Logo](http://geneticcoder.com/EasyImage/ea.png)

**By:** Robert Parham | 
**License:** wtfpl.net WTFPL | 
**Version:** 2.7

EasyImage is an easy to use image manipulation library written entirely in PHP and implemented in a single PHP class.

## It's Easy!
The focus is on ease of use. 
* You need only include a single class file.
* You **don't** need ImageMagick. *This means EasyImage is going to be slightly slower than an ImageMagick solution, but will be more portable.*
* No clean up! Temporary resources are destroyed automagically.
* Uses a single constructor for all image types, so you don't have to remember a bunch of different function names.
* All editing methods are chainable, so you can achieve most image editing functionality in a single line.
* Output is a breeze too. To send the image to the browser simply echo the class instance, EasyImage will take care of headers and everything. There are also options to save the image locally or force it as a download.
* Works on ancient versions of PHP

## It's can do anything
(Almost...)
* Reads and writes Animated gifs
* Reads Photoshop project files (PSD)
* Writes to PDF format
* Resize, skew, layer, distort, replace colors, add watermarks, create text, create gradients, and a ton more.