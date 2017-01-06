<?php

/**
 * @file
 * Exports data from Freshdesk(tickets per company, comments) to a JSON file using Freshdesk REST API
 */

/** Getting json from $url using curl
 * @see http://php.net/manual/en/function.curl-setopt.php
 */
function get_json($url){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // -X
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // -H
  curl_setopt($ch, CURLOPT_USERPWD, 'username:password'); // -u
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

/*Getting comments from oldest to newest for specific ticket*/
function get_comments($ticket_id){
  $ticket_comments_url = 'https://domain.freshdesk.com/api/v2/tickets/'. $ticket_id.'/conversations';
  $json_c = json_decode(get_json($ticket_comments_url), true);
  if(!empty($json_c)) {
    $comments = array();

    foreach (array_reverse($json_c) as $comment) {
      $c = new StdClass();
      $c->cid = $comment['id'];
      $c->comment = $comment['body_text'];
      $c->timestamp = date('D\, jS F Y H:i:s', strtotime($comment['created_at']));
      $c->user_name = get_user($comment['user_id']);
      $comments[$comment['id']] = $c;

    }
  }

  else {
    return array();
  }

  return $comments;
}

/*Getting user name from $user_id */
function get_user($user_id){
  $contact_json_url = 'https://domain.freshdesk.com/api/v2/contacts/' . $user_id;
  $agent_json_url = 'https://domain.freshdesk.com/api/v2/agents/'. $user_id; //could be issues to access data if dont have admin rights on freshdesk

  $contact_json = json_decode(get_json($contact_json_url), true);

  if (!$contact_json){//If user isnt 'contact' look into 'agents'
    $agent_json = json_decode(get_json($agent_json_url), true);
    $user = $agent_json['contact']['name']; //Agent user name
  } else {
    $user = $contact_json['name']; //Contact user name
  }
  return $user; //User name
}

$company_id = 5000123456; //Freshdesk company id
$all_tickets_url = 'https://domain.freshdesk.com/api/v2/tickets?company_id='. $company_id;
$tickets_json = get_json($all_tickets_url);
echo "Generating JSON structure... can take a while... \r\n";
// create a container to store all data
$data = new StdClass();
$data->nodes = new StdClass();

$json_n = json_decode($tickets_json, true);

// Limit the amount of exported items for testing purposes
//$i=0;

foreach (array_reverse($json_n) as $id => $ticket) { //From oldest to newest

  $n = new StdClass();
  // basic properties
  $n->project = $company_id;
  $n->ticket_id = $ticket['id'];
  $n->created = date('D\, jS F Y H:i:s', strtotime($ticket['created_at']));
  $n->title = $ticket['subject'];
  $n->body = strip_tags($ticket['description']);
  $n->comments = get_comments($ticket['id']);

  $data->nodes->$id = $n;

/*  if ($i > 2) {
    break;
  }
  else {
    $i++;
  }*/

}

echo "Creating and exporting JSON... \r\n";

$json = json_encode($data);

// Export JSON array to a file.
file_put_contents('freshdesk_export.json', $json);

echo  "JSON file has been built \r\n";
