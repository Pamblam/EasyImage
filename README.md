![EasyImage v2.7 Logo](http://geneticcoder.com/EasyImage/ea.png)

**By:** Robert Parham | 
**License:** wtfpl.net WTFPL | 
**Version:** 2.7

[EasyImage](http://geneticcoder.com/EasyImage/) is an easy to use image manipulation library written entirely in PHP and implemented in a single PHP class.

## It's Easy!
The focus is on ease of use. 
* You need only include a single class file.
* You **don't** need ImageMagick. *This means EasyImage is going to be slightly slower than an ImageMagick solution, but will be more portable.*
* No clean up! Temporary resources are destroyed automagically.
* Uses a single constructor for all image types, `EasyImage::Create($file)`, so you don't have to remember a bunch of different function names.
* All editing methods are chainable, so you can achieve most image editing functionality in a single line. `$img = EasyImage::Create($file)->scale(100)->borderRadius();`.
* Output is a breeze too. To send the image to the browser simply echo the class instance, EasyImage will take care of headers and everything. `echo EasyImage::Create($file)->greyScale();`. There are also options to save the image locally or force it as a download.
* Works on ancient versions of PHP.

## It can do anything!
(Almost...)
* Reads and writes Animated gifs.
* Reads Photoshop project files (PSD).
* Writes to PDF format.
* Resize, skew, layer, distort, replace colors, add watermarks, create text, create gradients, and a ton more. 
* Get information about the image such as size, mime type, a list of colors in the image, et cetera.
* ~~Call and wish you a happy birthday.~~ *Feature still in beta.*

Be sure to check out the docs [here](http://geneticcoder.com/EasyImage/) or download the docs.html file to see the complete list of features and functionality.

@todo - compile and add list of credits.