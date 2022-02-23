<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{#lib_path#}/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{#tpl_path#}/common/common.css" crossorigin="anonymous">
	{foreach from=$top_elements item=$i}
	{$i}
	{/foreach}

    <title>{$title|default:'CRM'}</title>
  </head>
  <body>
