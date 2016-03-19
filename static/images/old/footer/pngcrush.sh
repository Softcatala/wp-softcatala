#!/bin/sh

# loop through all files ending in .png
for file in `find -name '*.png'`
do

  # crush image and save it's output
  pngcrush -reduce -brute $file /tmp/crushed.png

  # overwrite original image with crushed version
  mv /tmp/crushed.png $file

done
