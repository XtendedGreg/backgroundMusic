#!/usr/bin/php82
<?php
# Background Music Player by XtendedGreg Nov 20, 2023
# Link to video: https://youtube.com/live/k6jqoLTWWPk
# Requires Packages "mpg123 php82 alsa-utils screen"
# Requires usercfg.txt with "dtparam=audio=on" on SD Card root

# Note: Change the runtime configuration at the bottom of this script

class backgroundMusic {

    public function __CONSTRUCT($timezone = 'US/Eastern', $device = "", $source = "/media/mmcblk0p1/music/", $temp = "/tmp/music/"){
        // Set Timezone for timestamps
        date_default_timezone_set($timezone);

        //Path to directory containing the music files
        $this->sourceDirectory = $source; // If this is another partition or a USB drive, place that path here

        $this->temporaryDirectory = $temp; // It is unlikely you would need to change this

        # Use "aplay -L" to determine device name
        # Raspberry Pi Analog Output (Pi 1,2,3,4): "default:CARD=Headphones"
        # Raspberry Pi HDMI Output (Pi Zero,5): "default:CARD=b1"
        $this->playbackDevice = $device;
        
        // Minimum amount of free space to keep on temp drive
        $this->reservedSpaceBytes = 10*1024*1024; //Keep 10MB Free      

        while(true){
            // Run the function main from the backgroundMusic class forever
            $this->main();
        }
    }

    private function get_availableSongs($directory){
        // Create array of song files
        $files = scandir($directory);
        if($files !== false){
            $availableFiles = [];
            foreach($files as $file){
                // Go through each file in the source directory
                if ($file != "." && $file != ".."){
                    // Exclude parent directory and current directory and add file name to array
                    $availableFiles[] = $file;
                }
            }
            if(count($availableFiles) > 0){
                // If the array is not empty, return the array
                return $availableFiles;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function get_availableSpace(){
        // Create temporary directory if not exists and get number of bytes available for the drive where it resides
        if(!file_exists($this->temporaryDirectory)) mkdir($this->temporaryDirectory, 0777, true); // This will create parent directories if required
        return disk_free_space($this->temporaryDirectory);
    }

    private function get_fileSize($file){
        // Get the size of the file in bytes
        if(file_exists($file)){
            $size = filesize($file);
            return $size;
        } else {
            return 0;
        }
    }

    private function copyFiles($availableFiles){
        // Determine what music files and how many to copy to the temporary directory and clean up music files that are not needed
        $availableSpace = $this->get_availableSpace();
        $neededSpace = 0;

        $currentFiles = $this->get_availableSongs($this->temporaryDirectory); // Get list of files in the temporary directory
        $usedSpace = 0;
        if($currentFiles !== false){
            foreach($currentFiles as $file){
                // If there are files in the temporary directory, calculate how much space they use
                $fileSpace = $this->get_fileSize($this->temporaryDirectory.$file);
                if($fileSpace !== false){
                    $usedSpace += $fileSpace;
                }
            }
        }

        $availableSpace += $usedSpace;  // add the size of existing temporary files to free space as if they were already deleted to get total amount of space that can be used

        $filesToCopy = [];
        if($availableFiles !== false && $currentFiles !== false){
            $filesToRemove = array_diff($currentFiles, $availableFiles); //Find files that were deleted from source and remove from temporary directory if exist
        } else if ($currentFiles !== false){
            $filesToRemove = $currentFiles; // No source files exist, so remove all temporary files
        } else {
            $filesToRemove = [];
        }

        // Check each file in availableFiles and determine how much can fit on the disk
        foreach($availableFiles as $file){
            $fileSpace = $this->get_fileSize($this->sourceDirectory.$file);
            if($fileSpace !== false){
                $neededSpace += $fileSpace;
                if($neededSpace >= $availableSpace - $this->reservedSpaceBytes){
                    if(!file_exists($this->temporaryDirectory.$file)){
                        // If file exists but will not fit when other files are copied, mark the temporary file for deletion
                        $filesToRemove[] = $file;
                    }
                } else {
                    if(!file_exists($this->temporaryDirectory.$file)){
                        // If the file is needed but does not exist, mark the file to be copied
                        $filesToCopy[] = $file;
                    }
                }
            }
        }

        // Clean up unneeded files from temp
        foreach($filesToRemove as $file){
            // Remove files from the temporary directory that will not be played as part of this cycle
            if(file_exists($this->temporaryDirectory.$file)){ // Make sure file exists in temporary directory before removing to prevent errors
                echo date("Y-m-d H:i.s T")." - PREP - Removing ".$this->temporaryDirectory.$file."\n";
                unlink($this->temporaryDirectory.$file);
            }
        }

        // Copy missing files to temp
        foreach($filesToCopy as $file){
            // Copy music that is not already in the temporary directory that are needed to be played this cycle
            if($this->get_fileSize($file) < $this->get_availableSpace() - $this->reservedSpaceBytes){ // Make sure drive is not full before attempting to copy
                echo date("Y-m-d H:i.s T")." - PREP - Copying ".$this->sourceDirectory.$file."\n";
                copy($this->sourceDirectory.$file, $this->temporaryDirectory.$file);
            }
        }
    }

    private function make_playlist($availableFiles){
        // Make a playlist from the files in the temporary directory in the order defined by availableFiles array
        $playlistFile = "/tmp/playlist_".date("Y-m-d_H.i.s_Z").".txt";
        if(file_exists($playlistFile)){
            // If the playlist file name already exists, remove it
            unlink($playlistFile);
        }
            $counter = 0;
            foreach($availableFiles as $file){
                if (file_exists($this->temporaryDirectory.$file)){
                    // If each file in the directory exists, put each entry as a new line in the playlist file
                    $counter++;
                    file_put_contents($playlistFile, $this->temporaryDirectory.$file."\n", FILE_APPEND);
                }
            }
            if($counter > 0){
                echo date("Y-m-d H:i.s T")." - PLAYLIST - ".$counter." Files in Playlist\n";
                return $playlistFile;
            } else {
                return false;
            }
    }

    private function play($playlist){
        // If the playlist file exists try to play it
        if (file_exists($playlist)){
            if ($this->playbackDevice != ""){
                $device = "-a ".$this->playbackDevice;
            } else {
                $device = "";
            }
            echo shell_exec("/usr/bin/mpg123 ".$device." --list ".$playlist); // Actual command that makes sound
            unlink($playlist); // Delete the playlist file since it is done
        }
    }

    private function main(){
        $availableFiles = $this->get_availableSongs($this->sourceDirectory); // Get list of available files
        shuffle($availableFiles); // Randomize list of available files
        $this->copyFiles($availableFiles); // Remove unneeded files and copy ones that are needed to temp if not already present
        $playlist = $this->make_playlist($availableFiles); // Create the playlist file based on what exists in the temporary directory
        if($playlist !== false){ // If there are files to play
            echo date("Y-m-d H:i.s T")." - PLAY - Playing Playlist: ".$playlist."\n";
            $this->play($playlist); //Play the files
            echo date("Y-m-d H:i.s T")." - PLAY - Done Playing Playlist: ".$playlist."\n";
        } else {
            // If there are no files to play, sleep for 1 second before trying again
            echo date("Y-m-d H:i.s T")." - PLAY - No files to play\n";
            sleep(1);
        }
    }

}

########################## Configuration ##########################
# Set your current timezone for accurate timestamps
$timezone = 'US/Eastern';

# Use "aplay -L" to determine device name
# Raspberry Pi Analog Output (Pi 1,2,3,4): "default:CARD=Headphones"
# Raspberry Pi HDMI Output (Pi Zero,5): "default:CARD=b1"
# Leave blank to use the system default device which may change as things are plugged and unplugged
$device = "default:CARD=Headphones";

# Path to directory containing the music files
$source = "/media/mmcblk0p1/music/"; // If this is another partition or a USB drive, place that path here

# Path to temporary location that is stored in RAM
$temp = "/tmp/music/"; // It is unlikely you would need to change this
###################################################################

// Initialize the class backgroundMusic from above which will run forever
$bg = new backgroundMusic($timezone, $device, $source, $temp);


?>
