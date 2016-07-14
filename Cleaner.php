<?php
include 'htmldom/simple_html_dom.php';
include 'Mytools.php';

Mylogic();

function Mylogic() {    
    $monil = new Mytools();
    $db = $monil->getDatabase();
    $myarray = array();
    $query = "SELECT email FROM emaildata";
    $result = $db->query($query);
    while($row = $result->fetch_assoc()) {
        $email = $row['email'];
	array_push($myarray,$email);
    }
    $i=0;
    foreach($myarray as $emailid)
    {
    	if(isValidEmail($emailid)){
	#	echo "valid";
	}    else {
		echo $emailid."\n";
		$db->query("delete from emaildata where email = \"$emailid\"");$i++;
	}
#	echo "\n";
    }
	echo "Deleted : $i";
}

function isValidEmail($email) 
{

	$case = array('someone','domain','example','username','abc','xxx','name@');
	if(contains($email,$case))
	{
		return false;
	}
	if(preg_match('/[\'^£$!%&*()}{#~?><>,|=+¬]/', $email))
	{
    		return false;
	}
	$ignore = array('gif','jpg','jpeg','png','js','css','htm','html');
	$ext = strtolower(pathinfo($email, PATHINFO_EXTENSION)); // Using strtolower to overcome case sensitive
	if (in_array($ext, $ignore)) {
	    	return false;
	} else {		
		$part = explode("@", $email);
		if(!is_numeric($part[0]))
		{
        		return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
		}
	}
}

 function contains($str,array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }


function domainCrawled($domain) {
    $monil = new Mytools();
    $db = $monil->getDatabase();
    $query = "update domainfound set status=1 where domain='$domain' limit 1";
    $db->query($query);    
    return $domain;
}

function getanydomain() {
    $monil = new Mytools();
    $db = $monil->getDatabase();
    $query = "SELECT domain FROM domainfound where status=0 and domain not like '%wordde%' and domain not like '%stack%' ORDER BY RAND() LIMIT 1";
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
            
    $domainfound = $db->prepare("INSERT INTO `domainfound`(`domain`, `foundfrom`, `adate`, `status`) VALUES (?,?,now(),0) ON DUPLICATE KEY UPDATE adate=now();");
    $domainfound->bind_param("ss", $newdomain , $domain);
        
    $stmt = $db->prepare("INSERT INTO `emaildata`(`domain`, `email`, `adate`) VALUES (?,?,now()) ON DUPLICATE KEY UPDATE adate=now();");
    $stmt->bind_param("ss", $domain, $email);

    $social = $db->prepare("INSERT INTO `socialdata`(`domain`, `link`, `adate`) VALUES (?,?,now()) ON DUPLICATE KEY UPDATE adate=now();");
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
