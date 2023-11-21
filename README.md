# backgroundMusic
Background Music Player as seen on XtendedGreg YouTube Video: https://youtube.com/live/k6jqoLTWWPk

## Installation
This installation instruction is based on Alpine Linux running on a Raspberry Pi with the apk community repository enabled
- Install the dependancies
```apk add mpg123 php82 alsa-utils screen unzip```
- Download the zip file of the git repository
```wget https://github.com/XtendedGreg/backgroundMusic/archive/refs/heads/main.zip```
- Unzip the zip file
```unzip backgroundMusic-main.zip```
- Remove Unzip Package
```apk del unzip```
- Copy files to their target locations:
```
cp Code/bin/backgroundMusic.php /bin/backgroundMusic.php
cp Code/etc/init.d/backgroundMusic /etc/init.d/backgroundMusic
mv /etc/motd /etc/motd.bak
cp Code/etc/motd /etc/motd
```
- Add executable permissions
```
chmod +x /bin/backgroundMusic.php
chmod +x /etc/init.d/backgroundMusic
```
- Add files to LBU to persist through reboots
```
lbu add /bin/backgroundMusic.php
lbu add /etc/init.d/backgroundMusic
```
- Add init as startup service
```rc-update add backgroundMusic default```
- Commit files to LBU
```lbu commit -d```
- Copy usercfg.txt to SD Card Root (This will overwrite any existing usercfg.txt file)
```
mount /media/mmcblk0p1 -o rw,remount
cp Code/media/mmcblk0p1/usercfg.txt /media/mmcblk0p1/usercfg.txt
mount /media/mmcblk0p1 -o ro,remount
```
- Reboot
```reboot```

## Add Music Files
Note: Music files must be in mpeg format like MP3
- Add on SD Card Directly
 - Insert SD Card into machine and open the drive in file explorer
 - If the "music" directory does not exist in the root of the drive, create it
 - Copy the music files to the "music" directory
 - Properly eject the SD Card
 - Boot the SD Card on the Raspeberry Pi
- Add files to the SD Card Using SFTP
 - From an SSH Console
 ```mount /media/mmcblk0p1 -o rw,remount```
 - Using an SFTP client like FileZilla, connect to the Pi and navigate to the "/media/mmcblk0p1" directory
 - If the "music" directory does not exist, create it using the SSH Console
 ```"mkdir /media/mmcblk0p1/music```
 - In the SFTP client, enter the "music" directory and copy music files to here
 - Close the SFTP client
 - From the SSH Console
 ```mount /media/mmcblk0p1 -o ro,remount```

## Runtime Commands
INIT: ```/etc/init.d/backgroundMusic [start|stop|restart]```
SCREEN: ```screen -r backgroundMusic```
The screen session will allow direct control of the MPG123 process using keyboard commands.
Note: To exit the screen session: press "CTRL+a" and then "d" or close the SSH console window

## Removal Commands
Run these commands from an SSH Console or from the terminal
```
/etc/init.d/backgroundMusic stop
rc-update del backgroundMusic
rm /bin/backgroundMusic.php
rm /etc/init.d/backgroundMusic
mount /media/mmcblk0p1 -o rw,remount
rm /media/mmcblk0p1/usercfg.txt
mount /media/mmcblk0p1 -o ro,remount
rm /etc/motd
mv /etc/motd.bak /etc/motd
lbu commit -d
```

## Like, Comment, Subscribe and Share
If you like this project, be sure to check out the linked video and drop a like and subscribe to the XtendedGreg YouTube channel for more fun projects and videos like this.  If you know anyone who might find these projects or videos interesting, be sure to share it with them!
