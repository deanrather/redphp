<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo $this->pageDetails['title']?></title>
<!--  Powered by redphp http://www.redphp.org -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php echo $this->pageDetails['styles']?>
<?php echo $this->pageDetails['scripts']?>
</head>
<body>
<?php $this->controller->view->show($this->pageDetails['view']); ?>

<?php echo $this->pageDetails['googleAnalytics']?>
</body>
</html>