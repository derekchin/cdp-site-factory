<?php

  function requestPage( $url )
    {
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

    }


function returnResponse($data)
{
  // TODO: set up responses for each CASE
  header('Content-Type: application/json');
  echo json_encode($data);
}

function getVerification($id, $name)
{
  $payload = "{ properties(where: {in: [" . $id . "]}) { edges { node {title } } } }";
  $endpoint = 'https://sb.builtbycharm.com/graphql?query=';
  $requestUrl = $endpoint . urlencode($payload);
  $curl = curl_init($requestUrl);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
  $response = curl_exec($curl);
  curl_close($curl);

  $json = json_decode($response, true);
  $serverUrl = $json["data"]["properties"]["edges"][0]["node"]["title"];

  return $name === $serverUrl;
}

function isDotCom($name)
{
  return strpos($name, '.com');
}

function createFolder($id, $name)
{
  if (getVerification($id, $name)) {
  // TODO: Test this command

  $createCommandsForServer = isDotCom($name) ? "&& ~/scripts/createFromTemplate $name $id" : "";
  $output = shell_exec("ssh cdp@107.161.164.46 \"./scripts/createFromTemplate $name\"" . $createCommandsForServer);
  //$output = shell_exec("~/scripts/createFromTemplate $name $id" . $createCommandsForServer);
  }
  returnResponse($output);
}

function zipFolder($name)
{
  // TODO: Get a set of commands to do this work
  $output = shell_exec("echo $name");
  returnResponse($output);
}

function backup()
{
  $output = shell_exec("~/scripts/backupToS3");
  returnResponse($output);
}

function isUsable($var)
{
  return isset($var) && !empty($var);
}

if ($_POST && $_POST['action']) {
  switch ($_POST['action']) {

    case "createFolders":
      if (isUsable($_POST['id']) && isUsable($_POST['folder'])) {
        createFolder($_POST['id'], $_POST['folder']);
      }
      break;

    case "processImages":
      if (isUsable($_POST['folder'])) {
        zipFolder($_POST['folder']);
      }
      break;

    case "updateCache":
      if (isDotCom($_POST['folder'])) {
        requestPage("http://". $_POST['folder'] ."/cache");

      }
      break;

    case "backup":
      backup();
      break;
  }
} else {
  echo "<html><body></body></html>";
}
