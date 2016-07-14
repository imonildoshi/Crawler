<?php

class Mytools {
    
    function get_domain($url)
    {
      $val = parse_url($url);
      $domain = str_replace('www.','', $val['host']);
      return $this->test_input($domain);
    }

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    public function getUserIpaddress()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
           $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function getHttpReferer()
    {
        if(isset($_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }
        else {
            return "unknown";
        }
    }

    public function call_curl($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4");
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);      // timeout on connect
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);     // timeout on response
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

        $str = curl_exec($curl);
        curl_close($curl);
        $dom = new simple_html_dom();
        $dom->load($str);
        return array('object' =>$dom, 'string' =>$str);
    }

    function contains($str,array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }
    
    function containsWhat($str,array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return $a;
        }
        return false;
    }

    public function check_url($url) {
        $file_headers = @get_headers($url);
        if(empty ($file_headers)) {
            return FALSE;
        }
        if(strpos($file_headers[0], '404')) {
            $exists = false;
        }
        else {
            $exists = true;
        }
        return $exists;
    }
    
    public function getEmailid($website)
    {     
        $data = $this->call_curl($website);
        $page = $data['string'];
        $email = array();
        $emailfinal = array();

        $pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";

        preg_match_all($pattern, $page, $matches);
        foreach($matches[0] as $emails){
            array_push($email, $emails);
        }
        
        foreach($email as $val){
            if(!in_array($val, $emailfinal, true)){
                if($this->isValidEmail($val)) {
                    array_push($emailfinal, $val);
                }
            }
        }
        return $emailfinal;
    }

    function unique_array_values($data4) {
        $data2 = array();
        foreach($data4 as $val){
            if(!in_array($val, $data2, true)){
                 array_push($data2, $val);
            }
        }
        return $data2;
    }

    function get_file_extension($file_name) {
	return substr(strrchr($file_name,'.'),1);
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



    public function getDatabase() {
        $servername = "localhost";
        $username = "root";
        $password = "qwerasdf";
        $dbname = "crawler";

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;

    }

}



?>
