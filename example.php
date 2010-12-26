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


include "multi-section.class.php";


$url = 'http://download.thinkbroadband.com/10MB.zip';


$dap = new DownloadAccelerator();

$dap->set_max_sections(5);
$dap->download($url );





?>