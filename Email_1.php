<?php
include 'htmldom/simple_html_dom.php';
include 'Mytools.php';

//Hi

if(empty ($argv[1])) {
    $websites = array_map('str_getcsv', file('data.csv'));
} else if($argv[1] != 1){    
    $websites = array("website" => array($argv[1]));    
} else {
    while (true) {
        $domain = getanydomain();
        Mylogic($domain);
    }
}

foreach($websites as $key) {
    foreach($key as $values) {
        Mylogic($values);
    }
}


function Mylogic($domain) {
    domainCrawled($domain);
    $limit = 20;
    $monil = new Mytools();
    $domain = $monil->test_input($domain);
    $website = "http://www.$domain";

    echo "\nScanning : ".$website."\n";
        
    $found = array();
    $ahref = array();
    $ahrefall = array();
    $ahrefgood = array();
    $finalemail = array();

    $mytestcase = array('support','contact','about','reach');
    $socialmedia = array('facebook.com','plus.google.com','linkedin.com',
                         'youtube.com','twitter.com','vk.com','mailto',
                         'pinterest.com','instagram.com','tumblr.com',
                         'feedburner.com','itunes.apple.com','store.ovi.com',
                         'play.google.com','windowsphone.com','appworld.blackberry.com');

    if(!checkWebsite($monil,$domain)) {
        return;
    }
        
    echo "\nGot the page ...\n";

    $webpage = $monil->call_curl($website);
	
 
    $html = $webpage['object'];


    foreach($html->find('a') as $e) {
        echo $e->href."\n";
        $str = str_replace("$website","",$e->href);                    
            array_push($ahrefall, $str);
    }

    $ahrefall = array_unique($ahrefall);

    if(count($ahrefall) == 0) {

	    echo "\n Trying again  ...\n";
	    $website = "http://$domain"; 

	    $webpage = $monil->call_curl($website);

	    $html = $webpage['object'];


	    foreach($html->find('a') as $e) {
		echo $e->href."\n";
		$str = str_replace("$website","",$e->href);
		    array_push($ahrefall, $str);
	    }

	    $ahrefall = array_unique($ahrefall);

    }

        
    foreach ($ahrefall as $value) {       
        if(((substr( $value, 0, 1 ) === "/") || (substr( $value, 0, 1 ) === "#") || ((substr( $value, 0, 4 ) != "http") && (substr( $value, 0, 4 ) != "java"))) &&  !(substr( $value, 0, 2 ) === "//"))  {
            if((substr( $value, 0, 1 ) != "/") && (substr( $value, 0, 1 ) != "#")) {
                $value = "/".$value;
            }
            $str = $website.$value;
            array_push($ahrefgood, $str);
        } else {
            if(substr( $value, 0, 4 ) !== "java") {
                $host = $monil->get_domain($value);                               
                if(!($monil->contains($value,$socialmedia))) {
                    array_push($found, $host);
                }    
                array_push($ahref, $value);
            }
        }
    }
    
    $found = $monil->unique_array_values($found);
    $ahref = $monil->unique_array_values($ahref);
    
    echo "\nlets start !!!\n";
        
    $finalemail = getEmailid($monil,$ahrefgood,$ahref,$mytestcase,$finalemail,$limit);    
        
    $sociallink = getSociallink($monil,$ahref,$socialmedia,$limit);

    $data = array('found' => $found, 'emailid' => $finalemail, 'social' => $sociallink, 'domain' => $domain);
    
    saveData($monil,$data);
    

}

function domainCrawled($domain) {
    $monil = new Mytools();
    $db = $monil->getDatabase();
    $query = "update domainfound set status=1 where domain=  '$domain' limit 1";
    $db->query($query);    
    return $domain;
}

function getanydomain() {
    $monil = new Mytools();
    $db = $monil->getDatabase();
    $query = "SELECT domain FROM domainfound where status=0 and domain not like '%wordde%' and domain not like '%stack%' and domain not like '%.cn' ORDER BY RAND() LIMIT 1";
    $result = $db->query($query);
    while($row = $result->fetch_assoc()) {
        $domain = $row['domain'];
    }
    return $domain;
}

function checkWebsite($monil,$domain) {
    $website = "http://www.$domain";
    if(!$monil->check_url($website)) {
        echo "\n".$website." does  not exist \n";
        $website = "http://$domain";
        echo "\nScanning : ".$website."\n";
        if(!$monil->check_url($website)) { 
            echo "\n".$website." does not exist \n";
            return false;
        }
    }
    return true;
}    

function saveData($monil,$data){         
    
    $found = $data['found'];
    $finalemail = $data['emailid'];
    $sociallink = $data['social'];
    $domain = $data['domain'];
        
    $db = $monil->getDatabase();
            
    $domainfound = $db->prepare("INSERT INTO `domainfound`(`domain`, `foundfrom`, `atime`, `status`) VALUES (?,?,now(),0) ON DUPLICATE KEY UPDATE atime=now();");
    $domainfound->bind_param("ss", $newdomain , $domain);
        
    $stmt = $db->prepare("INSERT INTO `emaildata`(`domain`, `email`, `atime`) VALUES (?,?,now()) ON DUPLICATE KEY UPDATE atime=now();");
    $stmt->bind_param("ss", $domain, $email);

    $social = $db->prepare("INSERT INTO `socialdata`(`domain`, `link`, `atime`) VALUES (?,?,now()) ON DUPLICATE KEY UPDATE atime=now();");
    $social->bind_param("ss", $domain, $link);

    foreach ($sociallink as $val) {
        $link = $val;
        $social->execute();
    }
    print_r($sociallink);
   
    foreach ($finalemail as $val) {
        $email = $val;
        $stmt->execute();
    }
    print_r($finalemail);
    
    foreach ($found as $value) {
       $newdomain = $value;
       $domainfound->execute();
    }
    print_r($found);
    
    $domainfound->close();
    $stmt->close();
    $social->close();
    $db->close();
    
}

function getSociallink($monil,$ahref,$socialmedia,$limit) {
    $count = 0;
    $sociallink = array();
    foreach ($ahref as $value) {
        if($monil->contains($value,$socialmedia)) {
            $count++;
            if($count > 10) { break; }
            array_push($sociallink, $value);
        }
    }
    return $sociallink;
}

function getEmailid($monil,$ahrefgood,$ahref,$mytestcase,$finalemail,$limit) {
    echo "\nScanning all good links !!!\n";
    $finalemail = checkcontact($monil,$ahrefgood,$mytestcase,$finalemail,$limit);
    $finalemail = checkall($monil,$ahrefgood,$finalemail,$limit);

    echo "\nScanning some poor one !!!\n";
    $finalemail = checkcontact($monil,$ahref,$mytestcase,$finalemail,$limit);
    $finalemail = checkall($monil,$ahref,$finalemail,$limit);

    $finalemail = $monil->unique_array_values($finalemail);
    return $finalemail;
}


function checkcontact($monil,$ahref,$mytestcase,$finalemail,$limit) {
    $count = 0;
    foreach ($ahref as $value) {
        if($monil->contains($value,$mytestcase)) {
         $count++;
         if($count > $limit) { break; }
         echo "\n".$value;
         $emailid = $monil->getEmailid($value);
            if(!empty($emailid)) {
                print_r($emailid);
                $finalemail = array_merge($finalemail,$emailid);
            }
        }
    }
    return $finalemail;
}

function checkall($monil,$ahref,$finalemail,$limit) {
    $count = 0;
    foreach ($ahref as $value) {
     $count++;
     if($count > $limit) { break; }
     echo "\n".$value;
     $emailid = $monil->getEmailid($value);
        if(!empty($emailid)) {
             print_r($emailid);
            $finalemail = array_merge($finalemail,$emailid);
        }
    }
    return $finalemail;
}

?>
