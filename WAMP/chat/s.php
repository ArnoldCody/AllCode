<?php
// ����һЩ�����ı���
$host = "localhost";
$port = 8000;
// ���ó�ʱʱ��
set_time_limit(0);
// ����һ��Socket
$socket = socket_create(AF_INET,SOCK_STREAM,0) or die("Could not create socket\n");
//��Socket���˿�
$result = socket_bind($socket,$host,$port) or die("Could not bind to socket\n");
// ��ʼ��������
$result = socket_listen($socket,3) or die("Could not set up socket listener\n");
// accept incoming connections
if(1)
{
	// ��һ��Socket������ͨ��
	$connection = socket_accept($socket) or die("Could not accept incoming connection\n");
	// ��ÿͻ��˵�����
	//$request = socket_read($connection,1024) or die("Could not read request\n");
	
	//��������
	//$output = $request;
	$output = "first message\r\n";
	socket_write($connection,$output,strlen($output)) or die("Could not write output\n");
	
	$str = "I am Ronny from server\r\n";
	socket_write($connection,$str);
	
	$request = socket_read($connection,1024) or die("Could not read request\n");
	echo $request;
	// �ر�sockets
	socket_close($connection);
}
//socket_close($socket);
?>