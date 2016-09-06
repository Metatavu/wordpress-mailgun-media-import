<?php
/*
  Created on Sep 3, 2016
  Plugin Name: Mailgun Media Import
  Description: Plugin for importing attachments into media library
  Version: 0.3
  Author: Antti Leppä / Metatavu Oy
*/

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once(ABSPATH . 'wp-includes/user.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/media.php');
require_once(ABSPATH . 'wp-includes/functions.php');

require_once("settings.php");
require_once("mailgun-downloader.php");
require_once("foogallery-importter.php");
require_once("media-importter.php");
require_once("image-editor.php");

$path = $_SERVER['REQUEST_URI'];

if (($path == "/mailgun-media-import") && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
  // TODO: These should be configurable
	
  wp_set_current_user(0);
  $maxWidth = 1280;
  $maxHeight = 1280;
  
  $timestamp = $_POST['timestamp'];
  $token = $_POST['token'];
  $signature = $_POST['signature'];
	
  if (!isset($timestamp) || !isset($token) || !isset($signature)) {
  	error_log("Missing $timestamp, $token or $signature");
  	echo "Bad Request";
  	http_response_code(400);
  	die;
  }
  
  $mailgunDownlader = new MailgunDownloader();
  $mediaImportter = new MediaImportter();
  $fooGalleryImporter = new FooGalleryImporter();
  
  if (!$mailgunDownlader->checkSignature($timestamp, $signature, $token)) {
  	error_log("$signature does not match");
  	echo "Forbidden";
  	http_response_code(403);
  	die;
  }
  
  $subject = $_POST['subject'];
  $bodyPlain = $_POST['body-plain'];
  $attachments = $_POST['attachments'];
  
  if (!isset($attachments)) {
  	error_log("Attachments could not be found from the request body");
  	echo "Attachments could not be found from the request body";
  	http_response_code(400);
  	die;
  }
  
  $downloaded = $mailgunDownlader->downloadFirstAttachment($attachments);
  if (!isset($downloaded)) {
  	error_log("Could not download file");
  	echo "Could not download file";
  	http_response_code(500);
  	die;
  }
  
  $imageEditor = new ImageEditor($downloaded);
  $imageEditor->fixOrientation();
  $imageEditor->scaleImage($maxWidth, $maxHeight);
  $saved = $imageEditor->save();
  
  $importtedImageId = $mediaImportter->createImage($saved, $subject, $bodyPlain);
  if (!isset($importtedImageId)) {
  	error_log("Could not import image");
  	echo "Could not import image";
  	http_response_code(500);
  	die;
  }
  
  if ($fooGalleryImporter->isEnabled()) {
  	$fooGalleryImporter->importImage($importtedImageId);
  }
}

?>