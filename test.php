<?php
/**
 * Created by [BombSquad Inc](http://www.bmbsqd.com)
 * User: Andy Hawkins
 * Date: 4/10/15
 * Time: 3:27 PM
 */

include "lib/Threading.php";

use \MultiPhreading\Threading;
use \MultiPhreading\Runnable;
use \MultiPhreading\SharedMemory;
use \MultiPhreading\SharedQueue;

class testRunnable implements Runnable {
	function run() {
		echo "\nHello, This is threaded speaking.";

		echo "\nReading From SharedMemory";
		$sham = new SharedMemory('MSQ');
		echo "\nHello ".$sham->Hello;
		echo "\nAndy ".json_encode($sham->Andy);

		$msq = new SharedQueue();
		echo "\nReading Queue. Total Messages: ".$msq->size();

		foreach($msq->fetch() as $msg)
		{
			echo "\n".$msg;
			break;
		}

		sleep(300);
		echo "\nHanging up\n\n";
	}
}

echo "\nStuffing SharedMemory.";
$sham = new SharedMemory('MSQ');
$sham->Hello = 'World';
$sham->Andy = ['Rocks'];

$msq = new SharedQueue();
foreach(range(1,3) as $n)
	$msq->publish('Threaded Queue Message: '.$n);

echo "\nStarting Thread";
Threading::run(new testRunnable());
Threading::run(new testRunnable());
Threading::run(new testRunnable());
echo "\nHello, This is master, Are you there thread?";
sleep(1);
echo "\n"; passthru('ps aux | grep php | grep test');
sleep(300);
echo "\nHanging up\n\n";
exit();