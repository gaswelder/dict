<?php
function tpl($name, $vars = [])
{
	$__name = $name;
	$__path = $GLOBALS['__APPDIR'] . "/templates/$name.php";

	if (!file_exists($__path)) {
		panic("could not find template file '$name'");
	}
	$src = file_get_contents($__path);
	preg_match_all('/\{\{(.*?)\}\}/', $src, $m);
	foreach ($m[0] as $i => $s) {
		$src = str_replace($s, '<?= htmlspecialchars('.$m[1][$i].') ?>',
			$src);
	}

	$__path = tempnam(sys_get_temp_dir(), 'tpl');
	file_put_contents($__path, $src);
	unset($src);
	unset($name);
	unset($m);
	unset($s);

	extract($vars);
	ob_start();
	try {
		include $__path;
		return ob_get_clean();
	} catch (Exception $e) {
		ob_clean();
		panic("error in template '$__name': ".$e->getMessage(), $e->getCode(), $e);
	}
}
