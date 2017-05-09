<?php
require_once('connect.php');
session_start();

$userid = $_SESSION['userid'];

/* 
  $activity will be an array indexed by timestamp,
  with each timestamp mapping to an array of events.
  each event will also be an array, with element [0] being
  a marker for the type of event:
  "c": comment / "l": like / "p": pledge / "m": material (project update).
  element [1] of event will be the relevant event info
*/

$activity = [];

// get all user's friends (followees)
$get_friends = "SELECT uid FROM Follow WHERE followerid='$userid'";
$friends = mysqli_query($conn, $get_friends);
if (!$friends) err_close();
while ($friend = mysqli_fetch_assoc($friends)) {
    $friend = $friend['uid'];

    // get all friends' comments
    $get_comments = "SELECT C.uid, C.text, P.pname, C.commenttime FROM Comment C JOIN Project P USING(pid) WHERE C.uid='$friend'";
    $comments = mysqli_query($conn, $get_comments);
    if (!$comments) err_close();
    while ($comment = mysqli_fetch_assoc($comments)) {
        if (array_key_exists($comment['commenttime'], $activity)) {
            $activity[$comment['commenttime']][] = ["c", $comment];
        } else {
            $activity[$comment['commenttime']] = [["c", $comment]];
        }
    }

    // get all friends' likes
    $get_likes = "SELECT L.uid, P.pname, L.liketime FROM `Like` L JOIN Project P USING(pid) WHERE L.uid='$friend'";
    $likes = mysqli_query($conn, $get_likes);
    if (!$likes) err_close();
    while ($like = mysqli_fetch_assoc($likes)) {
        if (array_key_exists($like['liketime'], $activity)) {
            $activity[$like['liketime']][] = ["l", $like];
        } else {
            $activity[$like['liketime']] = [["l", $like]];
        }
    }

    // get all friends' pledges
    $get_pledges = "SELECT Pl.uid, P.pname, Pl.amount, Pl.pledgetime FROM Pledge Pl JOIN Project P USING(pid) WHERE Pl.uid='$friend'";
    $pledges = mysqli_query($conn, $get_pledges);
    if (!$pledges) err_close();
    while ($pledge = mysqli_fetch_assoc($pledges)) {
        if (array_key_exists($pledge['pledgetime'], $activity)) {
            $activity[$pledge['pledgetime']][] = ["p", $pledge];
        } else {
            $activity[$pledge['pledgetime']] = [["p", $pledge]];
        }
    }
}

// get all projects user likes / has funded
$get_projects = "SELECT pid FROM `Like` WHERE uid='$userid' UNION SELECT pid FROM Pledge WHERE uid='$userid'";
$projects = mysqli_query($conn, $get_projects);
if (!$projects) err_close();
while ($project = mysqli_fetch_assoc($projects)) {
    $project = $project['pid'];

    // get all posts from project
    $get_posts = "SELECT P.pname, M.text, M.attachment, M.addtime FROM Project P JOIN Material M USING(pid) WHERE P.pid='$project'";
    $posts = mysqli_query($conn, $get_posts);
    if (!$posts) err_close();
    while ($post = mysqli_fetch_assoc($posts)) {
        if (array_key_exists($post['addtime'], $activity)) {
            $activity[$post['addtime']][] = ["m", $post];
        } else {
            $activity[$post['addtime']] = [["m", $post]];
        }
    }
}

// sort events in reverse chronological order
krsort($activity);

function err_close() {
    echo mysql_errno() . ": " . mysql_error();
    mysqli_close($conn);
}
?>

<style>
  body {padding: 20px 20px;}
  ul {list-style: none;}
  li:before {
    font-family: "Glyphicons Halflings";
    font-size: 20px;
    float: left;
    position: relative;
    top: 5px;
    left: -20px;
  }
  li.comment:before {
    content: "\270f";
  }
  li.like:before {
    content: "\e125";
  }
  li.pledge:before {
    content: "\e225";
  }
  li.material:before {
    content: "\e146";
  }
</style>


<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User's Home Page</title>
  <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
</head>
<body>
  <div class="container">
    <div class="col-md-8">
      <div class="page-header">
        <h2>Recent Activity</h2>
      </div>
      <ul>

        <?php
            foreach ($activity as $events) {
                foreach ($events as $e) {
                    $e_type = $e[0];
                    $e = $e[1];
                    if ($e_type == "c") {
                        echo
                        "<li class='comment'>
                          <div class=\"row\">
                            <div class='well well-sm pull-left'>
                              ".$e['uid']." commented on ".$e['pname'].":<br>"
                              .$e['text']."
                            </div>
                          </div>
                        </li>";
                    } elseif ($e_type == "l") {
                        echo
                        "<li class='like'>
                          <div class=\"row\">
                            <div class='well well-sm pull-left'>
                              ".$e['uid']." likes ".$e['pname']."
                            </div>
                          </div>
                        </li>";
                    } elseif ($e_type == "p") {
                        echo
                        "<li class='pledge'>
                          <div class=\"row\">
                            <div class='well well-sm pull-left'>
                              ".$e['uid']." pledged $".$e['amount']." to ".$e['pname']."
                            </div>
                          </div>
                        </li>";
                    } elseif ($e_type == "m") {
                        echo
                        "<li class='material'>
                          <div class=\"row\">
                            <div class='well well-sm pull-left'>
                              ".$e['pname']." posted an update:<br>".$e['text']."<br>";
                        echo "<img src=\"data:image/jpeg;base64,".base64_encode($e['attachment'])."\"/>               
                            </div>
                          </div>
                        </li>";
                    }
                }
            }
        ?>

      </ul>
    </div>

    <form action="projectlist.php" method="POST">
      <input type="text" name="project_name" placeholder="search project here">
      <input type="submit" name="search" value="Search">
    </form>
<div class="row">
        <div class="col-md-4 col-md-offset-4 text-center">    
        <h4> <a href='projectcreate.php'>Click here to create New Project.</a></h4>
        <h4> <a href='owner_project.php'>Update the existing Project.</a></h4>
        </div>
</div>
  </div>
</body>
</html>
