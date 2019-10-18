<?php
function sendSetPwdMail($mailAddr, $name, $hash) {
  $email = new \SendGrid\Mail\Mail();
  $email->setFrom('noreply@gcapitalonline.com', 'G Capital Online Team');
  $email->setSubject("Set Password");
  $email->addTo($mailAddr);
  $email->addContent(
    "text/html", "<strong>Dear " . $name . " ,</strong><br/>You can set your new password using <a target='blank_' href='" . baseURL . "setPwd/" . $hash . "'>this link " . baseURL . "setPwd/" . $hash . "</a>.<br/><br/>Regards, G Capital Online Team"
  );
  $sendgrid = new \SendGrid(sendGridKey);
  try {
    $response = $sendgrid->send($email);
    //echo "ok ";
  } catch (Exception $e) {
    //echo "error";
  }
}
function sendMktNoti($mailAddr, $name) {
  $email = new \SendGrid\Mail\Mail();
  $email->setFrom('noreply@gcapitalonline.com', 'G Capital Online Team');
  $email->setSubject("You've new price list assignment");
  $email->addTo($mailAddr);
  $email->addContent(
    "text/html", "<strong>Dear " . $name . " ,</strong><br/>You've a new price list assignment. Please login to Gbiz to check.<br/><br/>Regards, G Capital Online Team"
  );
  $sendgrid = new \SendGrid(sendGridKey);
  try {
    $response = $sendgrid->send($email);
    //echo "ok ";
  } catch (Exception $e) {
    //echo "error";
  }
}
function lowKeyCase($inp) {
  return array_map(function ($v) {return array_change_key_case($v);}, $inp);
}
function uuid() {
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );
}
