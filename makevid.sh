#!/bin/sh
mencoder "mf://output/*.png" -mf fps=10:type=png -ovc lavc -lavcopts vcodec=mpeg4:mbd=2:trell:vbitrate=4096 -oac copy -o /var/www-dev/misc/pxl2000/pxl2000.avi
