<html>
<head>
<title>Remote Server Test</title>
</head>
<body>
<form action="http://www.yourdomain.com/typo3conf/ext/remote_server/index.php" method="post" enctype="multipart/form-data">
<p>Click on "Test" button to test call to remote server. If successful you should see "HELO" in your browser.</p>
<input type="hidden" name="username" value="test" />
<input type="hidden" name="userident" value="<? echo md5('test'); ?>" />
<input type="hidden" name="serviceID" value="remote_server::helo" />
<input type="submit" value="Test" />
</form>
</body>
</html>