<?php

function cp($in, $out, $mod = null)
{
    echo sprintf("Copy %s to %s", $in, $out) . PHP_EOL;
    $res = copy($in, $out);
    if (!$res) {
        echo sprintf('  Something bad happened...') . PHP_EOL;
    }
    if ($res && $mod) chmod($out, $mod);
    return $res;
}

function transform($filename)
{
    $text = file_get_contents($filename);
    $data = @unserialize($text);
    if (is_bool($data)) {
        echo 'Cannot decode data' . PHP_EOL;
        return;
    }

    $replacements = [
        'inviqa.atlassian.net' => 'fixtures.jira.phar',
        'zgal@inviqa.com' => 'zgal@fixtures.jira.phar',
        '@inviqa.com' => '@fixtures.jira.phar',
        'Inviqa' => 'Company',
        'TWVODA' => 'PROJ',
        'INVGEN' => 'GEN',
    ];

    array_walk_recursive(
        $data,
        function(&$value, $key) use ($replacements) {
            if (!is_scalar($value)) {
                return;
            }
            foreach ($replacements as $from => $to) {
                $value = str_replace($from, $to, $value);
            }
        }
    );

    // collect users and display names
    $users = ['hilary', 'tanaka de-silva'];
    $names = ['Hilary Boyce'];
    array_walk_recursive(
        $data,
        function(&$value, $key) use (&$users, &$names) {
            if ($key == 'emailAddress') {
                list ($user,) = explode('@', $value, 2);
                $users[] = $user;
            }
            if ($key == 'name') {
                $users[] = $value;
            }
            if ($key == 'displayName') {
                $names[] = $value;
            }
        }
    );
    $users = array_unique($users);
    $names = array_unique($names);
    sort($users);
    sort($names);

    // fake data
    array_walk_recursive(
        $data,
        function(&$value, $key) use ($users, $names) {
            if (!is_string($value)) {
                return;
            }
            foreach ($users as $id => $user) {
                if ($user == 'zgal') {
                    continue;
                }
                $value = str_replace($user, 'user' . $id, $value);
            }
            foreach ($names as $id => $name) {
                if ($name == 'Zsolt Gal') {
                    continue;
                }
                $value = str_replace($name, 'Jira User ' . $id, $value);
            }
            if ($key == 'comment') {
                $value = 'Comment';
            }
        }
    );

    return file_put_contents($filename, serialize($data));
}

$inDir = getenv('HOME') . '/.jira.api_cache.php';
$outDir = __DIR__ . '/jira';

foreach (glob($inDir . '/*') as $infile) {
    if (strpos($infile, '.ttl') !== false) {
        continue;
    }

    $outfile = $outDir . '/' . basename($infile);
    $replacements = [
        'inviqa.atlassian.net' => 'fixtures.jira.phar',
        'zgal@inviqa.com' => 'zgal@fixtures.jira.phar',
        '@inviqa.com' => '@fixtures.jira.phar',
        'Inviqa' => 'Company',
        'TWVODA' => 'PROJ',
        'INVGEN' => 'GEN',
    ];
    $outfile = str_replace(array_keys($replacements), array_values($replacements), $outfile);
    if (is_file($outfile)) {
        echo "Fixture $outfile exists, skipping" . PHP_EOL;
        continue;
    }
    cp($infile, $outfile);
    if (false !== transform($outfile)) {
        echo "Replaced $outfile" . PHP_EOL;
    } else {
        echo "Cannot replace $outfile" . PHP_EOL;
    }
}
