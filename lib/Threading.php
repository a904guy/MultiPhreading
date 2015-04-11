<?php
/**
 * Created by [BombSquad Inc](http://www.bmbsqd.com)
 * User: Andy Hawkins
 * Date: 4/10/15
 * Time: 3:14 PM
 */

namespace MultiPhreading;

/* Threading */

class Threading {
	static function run( Runnable $runnable )
	{
		$pid = pcntl_fork();
		if( $pid == -1 ) {
			throw new \ErrorException('Inability to Launch Runnable');
		}elseif( $pid )
		{
			// Parent
			return $pid;
		}else{
			//Child
			register_shutdown_function(function() {
				posix_kill(posix_getpid(),SIGHUP);
			});
			$runnable->run();
			exit(1);
		}
	}
}

interface Runnable {
	public function run();
}

/* Threading End */

/* Shared Memory */

class SharedMemory {
	// Defaults 1 MegaByte of Shared Memory

	private $keyLocation;
	private $keyIdentifier;
	private $sharedMemoryId;
	public function __construct($keyIdentifier='MultiPhreading',$MemorySize=1000000,$perms=0666) {
		$this->keyIdentifier = $keyIdentifier;
		$this->keyLocation = '/tmp/'.$keyIdentifier;
		if(!is_file($this->keyLocation))
			touch($this->keyLocation);

		$ftOk = ftok($this->keyLocation,'a');
		$this->sharedMemoryId = shm_attach($ftOk,$MemorySize,$perms);

		register_shutdown_function(function() {
			shm_detach($this->sharedMemoryId);
		});
	}

	public function __get($k)
	{
		$key = crc32($k);
		if(!shm_has_var($this->sharedMemoryId,$key)) return null;
		return shm_get_var($this->sharedMemoryId,$key);
	}

	public function __set($k,$v)
	{
		shm_put_var($this->sharedMemoryId,(int) crc32($k),$v);
	}
}

class SharedQueue {
	private $queueName;
	private $topicName;
	private $queue;

	public function __construct($identifier='MultiPhreading',$topic='MultiPhreading')
	{
		$this->queueName = (int) crc32($identifier);
		$this->topicName = (int) crc32($topic);

		$this->queue = msg_get_queue($this->queueName,0666);
	}

	public function publish($message)
	{
		msg_send($this->queue,$this->topicName,$message,true,false);
	}

	public function size()
	{
		return msg_stat_queue($this->queue)['msg_qnum'];
	}

	public function fetch()
	{
		$size = msg_stat_queue($this->queue)['msg_qbytes'];
		msg_receive($this->queue,$this->topicName,$msgType = 1,$size,$message,true);
		yield $message;
	}
}

/* Shared Memory End */