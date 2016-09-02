<?php

/**
 * @file
 * Exports data from Open Atrium (Drupal 6) to a JSON file.
 * @see http://ericlondon.com/2012/03/10/drupal-6-export-all-drupal-data-to-json-from-a-drush-script.html
 */

/**
 * Build JSON array from comment data.
 * @param  integer  $nid ID of the node that holds the comments
 * @param  integer $pid ID of the parrent comment
 * @return string       JSON array with comment data.
 */
function get_node_comments_recursive($nid, $pid = 0) {

  $sql = "
    select *
    from {comments}
    where nid = %d and pid = %d
    order by thread asc
  ";
  $resource = db_query($sql, $nid, $pid);

  $comments = array();
  while ($row = db_fetch_object($resource)) {

    $c = new StdClass();
    $c->cid = $row->cid;
    $c->pid = $row->pid;
    $c->nid = $row->nid;
    $c->uid = $row->uid;
    $c->subject = $row->subject;
    $c->comment = $row->comment;
    $c->hostname = $row->hostname;
    $c->timestamp = date(EXPORT_DATE_FORMAT, $row->timestamp);
    $c->status = $row->status;
    $c->thread = $row->thread;
    $c->user_name = $row->name;

    $comments[$row->cid] = $c;
  }
  if (empty($comments)) {
    return array();
  }

  foreach ($comments as $key => $value) {
    $children = get_node_comments_recursive($nid, $value->cid);
    if (!empty($children)) {
      $comments[$key]->children = $children;
    }
  }

  return $comments;
}

// Open Atrium project ID
$project_id = 3300;

// Load all nodes that belong to the project
$sql = 'select nid from {og_ancestry} where group_nid=' . $project_id . ' order by nid asc';

echo "Fetching node data... \r\n";
$resource = db_query($sql);
while($row = db_fetch_object($resource)) {
  $node = node_load($row->nid);
  if (is_object($node)) {
    // This is lame... can do better..
    if ($node->type == 'casetracker_basic_case') {
      $nodes[] = $node;
    }
  }
}

echo "Generating JSON structure... \r\n";
// create a container to store all data
$data = new StdClass();
$data->nodes = new StdClass();

// Limit the amount of exported items for testing purposes
// $i = 0;

foreach ($nodes as $nid => $node) {

  $n = new StdClass();

  // basic properties
  $n->project = 3300;
  $n->nid = $node->nid;
  $n->type = $node->type;
  $n->uid = $node->uid;
  $n->user_name = $node->name;
  $n->status = $node->status;
  $n->created = date(EXPORT_DATE_FORMAT, $node->created);
  $n->changed = date(EXPORT_DATE_FORMAT, $node->changed);
  $n->title = $node->title;
  $n->body = $node->body;

  // comments (recursive)
  if ($node->comment_count) {
    $n->comments = get_node_comments_recursive($n->nid);
  }

  $data->nodes->$nid = $n;

  // if ($i > 3) {
  //   break;
  // }
  // else {
  //   $i++;
  // }
}

echo "Creating and exporting JSON... \r\n";
// Create JSON array
$json = json_encode($data);

// Export JSON array to a file.
file_put_contents('drupal_export.json', $json);

echo  "JSON file has been built \r\n";