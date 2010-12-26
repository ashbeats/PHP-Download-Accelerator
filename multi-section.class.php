<?php
/***
 * 
 * 	PHP Download Accelerator - Curl / Sections / Threads
 * 	ashbeats[@]gmail.com 
 *  
 *  Download a file in a manner simillar to download accelerators by
 *  breaking up the file into sections and downloading each section
 *  simultaneously. Finally stiching file together on server. 
 *  
 *  v.b.f.e -  very buggy first edition.
 *  
 */

class DownloadAccelerator
{    
	public $tmp_folder = "D:/xampp 2/Multithread-downloader/tmp/"; 	
	public $max_sections = 5;
	
    function __construct()
    {
    	
		// TODO : Check tmp folder to see if writable		
    }
	
	
	
	public function download($url, $username='', $password='')
	{
		
        $timer = new Timer();
		$timer->Start();
		
        try
        {        
            $no_of_parts = $this->max_sections;
			
            $fullsize = $this->remote_filesize($url);
        
            $parts = $this->calculate_splits($fullsize, $no_of_parts);
        
			if( empty($username) == false &&  empty($password) == false )
			{
				$this->download_parallel($url, $parts, $username, $password);
			}else{
				 $this->download_parallel($url, $parts);
			}
                               
           
			
			$totaltime = $timer->End();
			echo "This download completed in ". floor($totaltime) ." seconds <br />"; 
			echo "Avg Speed: ".floor($fullsize/$totaltime)/1000 ." KBps <br />"; 
			
			return basename($url) . " downloaded to " . $this->tmp_folder . basename($url);
			
			
        
        }
        catch(Exception $e)
        {
            echo $e->getMessage();			
			// Clean up
			$this->clean_files( basename($url) );
			
			$timer->End();
			
			return false;
			
        }
		

	}




	// SETTERS
	public function set_max_sections($max_sections)
    {
        $this->max_sections = $max_sections;
    }
    
    
  
    public function set_tmp_folder($tmp_folder)
    {
        $this->tmp_folder = $tmp_folder;
    }




    // ---- Functions -- --- //

    function calculate_splits($fullsize, $no_of_parts)
    {
        $parts = array ();

        $tmp_size = $fullsize;
        $section_size = floor($fullsize/$no_of_parts);
        $modulus = $fullsize%$no_of_parts;


        for ($i = 1; $i <= $no_of_parts; $i++)
        {
            $parts[$i]['start'] = $tmp_size;

            if ($modulus != 0 & $i == $no_of_parts-1)
            {
                $parts[$i]['offset'] = $tmp_size-$section_size-$modulus;
                $tmp_size = $tmp_size-$section_size-$modulus-1;
            }
            else
            {
                $parts[$i]['offset'] = $tmp_size-$section_size;
                $tmp_size = $tmp_size-$section_size-1;
            }

            if ($parts[$i]['offset'] < 0)
            {
                $parts[$i]['offset'] = 0;
            }
        }

        return $parts;


    }


    function download_parallel($file, $parts, $user = "", $pw = "", $save_to = '')
    {

        $mh = curl_multi_init();
       $tmp_folder =  $this->tmp_folder;
        $save_to = $tmp_folder;

        $i = 0;
        foreach ($parts as $part)
        {

            $g = $save_to.basename($file).'.00'.$i;

            $range = $part['offset'].'-'.$part['start'];

            if (!is_file($g))
            {
                $conn[$i] = curl_init($file);
                $fp[$i] = fopen($g, "w");

                if (! empty($user) && ! empty($pw))
                {
                    $headers = array ('Authorization: Basic '.base64_encode("$user:$pw"));
                    curl_setopt($conn[$i], CURLOPT_HTTPHEADER, $headers);

                }

                curl_setopt($conn[$i], CURLOPT_FILE, $fp[$i]);
                curl_setopt($conn[$i], CURLOPT_HEADER, 0);
                curl_setopt($conn[$i], CURLOPT_CONNECTTIMEOUT, 60);
                curl_setopt($conn[$i], CURLOPT_RANGE, $range);
                curl_setopt($conn[$i], CURLOPT_BINARYTRANSFER, 1);



                curl_multi_add_handle($mh, $conn[$i]);
            }

            $i++;
        }



        //process
        do
        {
            $n = curl_multi_exec($mh, $active);
			
			//print_r($n);
			
        }
        while ($active);

        $i = 0;
        foreach ($parts as $part)
        {
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
            fclose($fp[$i]);

            $i++;
        }
        curl_multi_close($mh);
		
		$this->merge_files( basename($file) );
		 //$this->merge_files( basename($url) );

    }


    function remote_filesize($url, $user = "", $pw = "")
    {
        ob_start();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);

        if (! empty($user) && ! empty($pw))
        {
            $headers = array ('Authorization: Basic '.base64_encode("$user:$pw"));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $ok = curl_exec($ch);
        curl_close($ch);
        $head = ob_get_contents();
        ob_end_clean();

        $regex = '/Content-Length:\s([0-9].+?)\s/';
        $count = preg_match($regex, $head, $matches);

        return isset ($matches[1])?$matches[1]:"unknown";
    }


    function merge_files($file)
    {
        $content = '';

        $tmp_folder =  $this->tmp_folder;
        $items = glob($tmp_folder.basename($file).'.*');


        foreach ($items as $filename)
        {

            $file_size = filesize($filename);
            $handle = fopen($filename, 'rb') or die ("error opening file");
            $content .= fread($handle, $file_size) or die ("error reading file");



        }


        //write content to merged file
        $handle = fopen($tmp_folder.basename($file), 'wb') or die ("error creating/opening merged file");
        fwrite($handle, $content) or die ("error writing to merged file");

        // CLean up
        foreach ($items as $filename)
        {
            unlink($filename);
        }

        return 'OK';
    }


    function clean_files($file)
    {
        $content = '';

        $tmp_folder =  $this->tmp_folder;
        $items = glob($tmp_folder.basename($file).'.*');


        foreach ($items as $filename)
        {

            $file_size = filesize($filename);
            $handle = fopen($filename, 'rb') or die ("error opening file");
            $content .= fread($handle, $file_size) or die ("error reading file");
        }


        //write content to merged file
        $handle = fopen($tmp_folder.basename($file), 'wb') or die ("error creating/opening merged file");
        fwrite($handle, $content) or die ("error writing to merged file");
        return 'OK';
    }//end of function merge_file




}




class Timer
{
	var $starttime; 
	var $endtime; 
	
	
    function Start()
    {
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1]+$mtime[0];
        $this->starttime = $mtime;
    }
	
    function End()
    {
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1]+$mtime[0];
        $this->endtime = $mtime;
        return $totaltime = ($this->endtime - $this->starttime);
    }


}



?>