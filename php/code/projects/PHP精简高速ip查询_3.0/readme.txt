IP��ַ��ѯ3.0
��ʾ��ַ��http://tbip.sinaapp.com
1.IP��ַ��ѯphp�棬�����Ա�IP��ַ�⣬�ǳ��ľ�׼�����һ������Ա�IP��ַ��ʵʱ���¡���������õ����ݽ��������µġ�
2.��3.0���ĺ���������� ����2.0 1.0 �汾��ȫ��Դ
3.define('SINA_SAE', '0'); //�Ƿ���������SAEƽ̨1Ϊʹ��0Ϊ��ʹ��
4.define('REWRITE', '0'); //�Ƿ�����α��̬
5.����WhoIs��Ϣ��ѯ�ӿ�

.htaccess ͨ��α��̬���� ����������ο�����Ľ����޸�
RewriteEngine on
RewriteRule ^(.*)$ index.php?id=$1 [L]

sina SAEר��α��̬����
- rewrite: if (!is_dir() && !is_file() && path ~ "/(.*)" ) goto "index.php?$1"

�ļ�˵��
index.php      ������
function.php   ������
 
�����ļ�
readme.txt     ˵���ļ�
taobaoapi.txt  �Ա�api�ӿ�˵��
htaccess.txt   ͨ��α��̬����
config.yaml    sina SAEα��̬����
cacheip.txt    ���ݻ����ļ�
bg_body.jpg    ����ͼ
whois.php      WhoIs��Ϣ��ѯ�ӿ�

����SAE���php�ռ������ַ:http://sae.sina.com.cn/activity/invite/101149/weibo
����SAE���php�ռ�ע�Ṧ��:http://hbwanghai.blog.163.com/blog/static/199297147201222310226519/


���´��빫����ַ:http://xiaogg.ctdisk.com/u/349707/437278

�Ƽ�����ip��ѯ���ߣ�http://ip.1tt.net ���ô���IP�Ȿ�ؿ�
���ص�ַ:http://www.ctdisk.com/file/1048404


2.0����
http://sae.sina.com.cn/?m=apps&a=detail&aid=103
1.0����
http://www.ctdisk.com/file/6453343
http://down.chinaz.com/soft/32286.htm