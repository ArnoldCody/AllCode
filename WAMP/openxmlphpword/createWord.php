<?php
/*
	�汾��0.1
	���ߣ�͵���ӵ�(monkee)
	������ͨ����DOCX�ļ����������ַ����滻����ʵ��WORD�ļ����������ɡ�
	Ҫ��PHP>5.0��ģ���ļ�������MS OFFICE 07�������ϵ�DOCX�ļ���������ODT�ļ���Ŀ¼�е�apache�ļ��о��п�дȨ�ޡ�
	ʹ�ã��������˵����
	������
		$message	�滻ģ���ļ������飬���磺$message['title']��ֵ���滻 {title} ���֡�
		$template	ģ���ļ�
		$wordfile	���ɵ��ļ�
*/
define("MK_PATH","");	//��������ļ���λ�� ĩβ�ӡ�/��
include_once("pclzip.lib.php");
function createWord($message,$template,$wordfile)
{
	$file_ext=MK_PATH."/apche/0";
	$sc_rep=array();	//�滻Դ
	$ds_rep=array();	//�滻Ŀ��
	foreach($message as $key=>$item)
	{
		$sc_rep[]='{'.$key.'}';
		$ds_rep[]=$item;
	}
	if(!is_dir($file_ext))
		mkdir($file_ext);
	$zip=new Pclzip($template);
	$zip->extract($file_ext);
	unset($zip);
	$content=@file_get_contents($file_ext."/word/document.xml");
	$content=str_replace($sc_rep,$ds_rep,$content);
	@file_put_contents($file_ext."/word/document.xml",$content);
	$zip=new Pclzip($wordfile);
	$zip->create($file_ext."/[Content_Types].xml,".$file_ext."/word,".$file_ext."/docProps,".$file_ext."/_rels",PCLZIP_OPT_REMOVE_PATH,$file_ext);
	//$zip->create("[Content_Types].xml,word,docProps,_rels");
	removeDir($file_ext);
	/*
	foreach(array("[Content_Types].xml","word","docProps","_rels") as $item)
	{
		removeDir($item);
	}
	*/
	return true;
}
function removeDir($dir)
{
	  if(is_dir($dir))
	  {
		  $s=scandir($dir);
		  foreach($s as $i)
			  if($i!="." && $i!="..")
				  removeDir($dir."/".$i);
		  @rmdir($dir);
	  }
	  elseif(is_file($dir))
		  @unlink($dir);
	  else
		  return false;
	  return true;
}
?>