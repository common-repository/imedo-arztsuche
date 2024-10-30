<?php
/**
 * Integrationsbibliothek zur Integration der imedo Arztsuche
 *
 * Vorrausetzungen:
 *  - PHP5
 *  - Curl
 *  - imedo API-Key
 *
 *
 * Version: 2.0.0
 * http://semver.org für weitere Informationen zur Version
 *
 *
 * Weitere Informationen zur Integration auf
 * http://developer.imedo.de
 *
 * (c) imedo GmbH - 2008-2011
 */

/**
 * Public API
 */
function render_integration_stylesheets($size, $host="doctors.imedo.de") {
  echo '<link rel="stylesheet" type="text/css" href="http://' . $host . '/stylesheets/docsearch-common.css" />';
  echo '<link rel="stylesheet" type="text/css" href="http://' . $host . '/stylesheets/docsearch-common-colors.css" />';
  echo '<link rel="stylesheet" type="text/css" href="http://' . $host . '/stylesheets/style-' . $size . '.css" />';
}

/**
 * Public API
 */
function render_integration_response($integration_response) {
  echo($integration_response["response_body"]);
}

/**
 * Public API
 */
function debug_response($integration_response) {
  print_r("BODY:");
  print_r($integration_response["response_body"]);
  print_r("SIZE:");
  print_r($integration_response["header_size"]);
  print_r("HEADER:");
  print_r($integration_response["response_header"]);
  print_r("END:");
}

/**
 * Public API
 */
function integrate_doctors($api_key) {
  return integrate("/practice/provider_search", $api_key, "doctors.imedo.de");
}

/*
 * Public API
 */
function integrate_questions($api_key) {
  return integrate("/community/questions", $api_key, "questions.imedo.de");
}

///////////////////////////////////////////////////////////////////////
// Private Funktionen ab hier - Nicht direkt aufrufen

/**
 * private
 * Integration und Anfrage an imedo
 */
function integrate ($start_page, $api_key, $host="imedo.de") {
  $url = create_integration_url($host, $start_page, $api_key, $_SERVER["REQUEST_URI"]);

  $ch = curl_init ( $url );
  curl_setopt ( $ch, CURLOPT_HEADER, 1 );
  curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
  curl_setopt ( $ch, CURLOPT_TIMEOUT, 10 );
  curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 0 );
  if(isset($_SERVER['HTTP_COOKIE']))
      curl_setopt ( $ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] );

  if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
  	curl_setopt ( $ch, CURLOPT_POST, 1 );
  	$post_data = file_get_contents ( "php://input" );	// RAW POST DATA
  	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
  }

  $response = curl_exec ($ch);
  if (strlen($response) > 0) {
    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $integration_response = array(
      "header_size" => $header_size,
      "response_header" => substr($response, 0, $header_size),
      "response_body" => substr($response, $header_size));
  } else {
      $integration_response = busy_response();
  }
  curl_close ($ch);

  set_integration_header($integration_response["response_header"]);
  return $integration_response;
}

/*
 * private
 * Erzeugung der Integrations-URL inkl. API-Key
 */
function create_integration_url($host, $start_page, $api_key, $request_url) {
  $action = split("\\?action=", urldecode($request_url));
  if (sizeof($action) == 2) {
    $action = $action[1];
  } else {
    $action = $start_page;
  }
  $pos = strpos($action, '?');
  if ($pos === false) {
    $url = "http://$host$action?api_key=$api_key";
  } else {
    $url = "http://$host$action&api_key=$api_key";
  }
  $url = ereg_replace(" ", "+", $url);
  return $url;
}

/*
 * private
 * Anzeige der Rückgabe, falls Anfrage fehlschlug
 */
function busy_response () {
  return array(
    "response_body" => join("\n", file(dirname(__FILE__) . '/remote_resource_busy.html')),
    "response_header" => "HTTP/1.0 503 Service Unavailable",
    "header_size" => strlen(32)); // 32 = Length of "HTTP/1.0 503..."
}

/**
 * private
 * Behandlung von Redirects und setzen des Status-Headers
 * Wichtig: Geht davon aus, dass die Integrationsseite (z.B. Arztsuche)
 *          die angepasste URL zurückliefert
 */
function set_integration_header($response_header) {
  $headers = http_parse_headers($response_header);
  if(isset($headers['Status'])){
      header("Status: ". $headers['Status']);
  }
  if(isset($headers['Set-Cookie'])){
      header("Set-Cookie: ". $headers['Set-Cookie']);
  }
  if(isset($headers['Status']) && isset($headers['Location']) && ($headers['Status'] == 301 || $headers['Status'] == 302 )) {
    header("Location: " . $headers['Location']);
    echo($integration_response['response_body']);
    die();
  }
}

/**
 * private
 * Übernommen von http://php.net/manual/en/function.http-parse-headers.php
 */
function http_parse_headers($headers=false) {
  if($headers === false){
    return false;
  }
  $headers = str_replace("\r","",$headers);
  $headers = explode("\n",$headers);
  foreach($headers as $value){
    $header = explode(": ",$value);
    if(isset($header[0]) && !isset($header[1])){
      $headerdata['status'] = $header[0];
    }
    elseif(isset($header[0]) && isset($header[1])){
      $headerdata[$header[0]] = $header[1];
    }
  }
  return $headerdata;
}