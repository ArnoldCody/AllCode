var  bsYear;
var  bsDate;
var  bsWeek;
var  arrLen=22;    //���鳤��
var  sValue=0;    //���������
var  dayiy=0;    //����ڼ���
var  miy=0;    //�·ݵ��±�
var  iyear=0;    //��ݱ��
var  dayim=0;    //���µڼ���
var  spd=86400;    //ÿ�������

var  year1999="30;29;29;30;29;29;30;29;30;30;30;29";    //354
var  year2000="30;30;29;29;30;29;29;30;29;30;30;29";    //354
var  year2001="30;30;29;30;29;30;29;29;30;29;30;29;30";    //384
var  year2002="30;30;29;30;29;30;29;29;30;29;30;29";    //354
var  year2003="30;30;29;30;30;29;30;29;29;30;29;30";    //355
var  year2004="29;30;29;30;30;29;30;29;30;29;30;29;30";    //384
var  year2005="29;30;29;30;29;30;30;29;30;29;30;29";    //354
var  year2006="30;29;30;29;30;30;29;29;30;30;29;29;30"; //384
var  year2007="29;29;30;29;29;30;29;30;30;30;29;30";    //354
var  year2008="30;29;29;30;29;29;30;29;30;30;29;30";    //354
var  year2009="30;30;29;29;30;29;29;30;29;30;29;30;30"; //384
var  year2010="30;29;30;29;30;29;29;30;29;30;29;30";    //354
var  year2011="30;29;30;30;29;30;29;29;30;29;30;29";    //354
var  year2012="30;29;30;30;29;30;29;30;29;30;29;30;29"; //384
var  year2013="30;29;30;29;30;30;29;30;29;30;29;30";    //355
var  year2014="29;30;29;30;29;30;29;30;30;29;30;29;30"; //384
var  year2015="29;30;29;29;30;29;30;30;30;29;30;29";    //354
var  year2016="30;29;30;29;29;30;29;30;30;29;30;30";    //355
var  year2017="29;30;29;30;29;29;30;29;30;29;30;30;30"; //384
var  year2018="29;30;29;30;29;29;30;29;30;29;30;30";    //354
var  year2019="30;29;30;29;30;29;29;30;29;29;30;30";    //354
var  year2020="29;30;30;30;29;30;29;29;30;29;30;29;30"; //384

var  month1999="����;����;����;����;����;����;����;����;����;ʮ��;ʮһ��;ʮ����"
var  month2001="����;����;����;����;������;����;����;����;����;����;ʮ��;ʮһ��;ʮ����"
var  month2004="����;����;�����;����;����;����;����;����;����;����;ʮ��;ʮһ��;ʮ����"
var  month2006="����;����;����;����;����;����;����;������;����;����;ʮ��;ʮһ��;ʮ����"
var  month2009="����;����;����;����;����;������;����;����;����;����;ʮ��;ʮһ��;ʮ����"
var  month2012="����;����;����;����;������;����;����;����;����;����;ʮ��;ʮһ��;ʮ����"
var  month2014="����;����;����;����;����;����;����;����;����;�����;ʮ��;ʮһ��;ʮ����"
var  month2017="����;����;����;����;����;����;������;����;����;����;ʮ��;ʮһ��;ʮ����"
var  month2020="����;����;����;����;������;����;����;����;����;����;ʮ��;ʮһ��;ʮ����"
var  Dn="��һ;����;����;����;����;����;����;����;����;��ʮ;ʮһ;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;��ʮ;إһ;إ��;إ��;إ��;إ��;إ��;إ��;إ��;إ��;��ʮ";

var  Ys=new  Array(arrLen);
Ys[0]=919094400;Ys[1]=949680000;Ys[2]=980265600;
Ys[3]=1013443200;Ys[4]=1044028800;Ys[5]=1074700800;
Ys[6]=1107878400;Ys[7]=1138464000;Ys[8]=1171728000;
Ys[9]=1202313600;Ys[10]=1232899200;Ys[11]=1266076800;
Ys[12]=1296662400;Ys[13]=1327248000;Ys[14]=1360425600;
Ys[15]=1391097600;Ys[16]=1424275200;Ys[17]=1454860800;
Ys[18]=1485532800;Ys[19]=1518710400;Ys[20]=1549296000;
Ys[21]=1579881600;

var  Yn=new  Array(arrLen);      //ũ���������
Yn[0]="��î��";Yn[1]="������";Yn[2]="������";
Yn[3]="������";Yn[4]="��δ��";Yn[5]="������";
Yn[6]="������";Yn[7]="������";Yn[8]="������";
Yn[9]="������";Yn[10]="������";Yn[11]="������";
Yn[12]="��î��";Yn[13]="�ɳ���";Yn[14]="������";
Yn[15]="������";Yn[16]="��δ��";Yn[17]="������";
Yn[18]="������";Yn[19]="������";Yn[20]="������";
Yn[21]="������";
var  D=new  Date();
var  yy=D.getFullYear();
var  mm=D.getMonth()+1;
var  dd=D.getDate();
var  ww=D.getDay();
if  (ww==0)  ww="<font  color=RED>������</font>";
if  (ww==1)  ww="����һ";
if  (ww==2)  ww="���ڶ�";
if  (ww==3)  ww="������";
if  (ww==4)  ww="������";
if  (ww==5)  ww="������";
if  (ww==6)  ww="<font  color=green>������</font>";
ww=ww;
var  ss=parseInt(D.getTime()  /  1000);
if  (yy<100)  yy="19"+yy;

for  (i=0;i<arrLen;i++)
if  (ss>=Ys[i]){
iyear=i;
sValue=ss-Ys[i];        //���������
}
dayiy=parseInt(sValue/spd)+1;        //���������

var  dpm=year1999;
if  (iyear==1)  dpm=year2000;
if  (iyear==2)  dpm=year2001;
if  (iyear==3)  dpm=year2002;
if  (iyear==4)  dpm=year2003;
if  (iyear==5)  dpm=year2004;
if  (iyear==6)  dpm=year2005;
if  (iyear==7)  dpm=year2006;
if  (iyear==8)  dpm=year2007;
if  (iyear==9)  dpm=year2008;
if  (iyear==10)  dpm=year2009;
if  (iyear==11)  dpm=year2010;
if  (iyear==12)  dpm=year2011;
if  (iyear==13)  dpm=year2012;
if  (iyear==14)  dpm=year2013;
if  (iyear==15)  dpm=year2014;
if  (iyear==16)  dpm=year2015;
if  (iyear==17)  dpm=year2016;
if  (iyear==18)  dpm=year2017;
if  (iyear==19)  dpm=year2018;
if  (iyear==20)  dpm=year2019;
if  (iyear==21)  dpm=year2020;
dpm=dpm.split(";");

var  Mn=month1999;
if  (iyear==2)  Mn=month2001;
if  (iyear==5)  Mn=month2004;
if  (iyear==7)  Mn=month2006;
if  (iyear==10)  Mn=month2009;
if  (iyear==13)  Mn=month2012;
if  (iyear==15)  Mn=month2014;
if  (iyear==18)  Mn=month2017;
if  (iyear==21)  Mn=month2020;
Mn=Mn.split(";");

var  Dn="��һ;����;����;����;����;����;����;����;����;��ʮ;ʮһ;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;ʮ��;��ʮ;إһ;إ��;إ��;إ��;إ��;إ��;إ��;إ��;إ��;��ʮ";
Dn=Dn.split(";");

dayim=dayiy;

var  total=new  Array(13);
total[0]=parseInt(dpm[0]);
for  (i=1;i<dpm.length-1;i++)  total[i]=parseInt(dpm[i])+total[i-1];
for  (i=dpm.length-1;i>0;i--)
if  (dayim>total[i-1]){
dayim=dayim-total[i-1];
miy=i;break;//2007/11/9������break�������ũ����ʾ�����³�һ
}
bsWeek=ww;
bsDate="<span class=month>"+mm+"��</span>";
bsDate2="<span class=day>"+dd+"</span>";
bsYear="ũ��"+Yn[iyear];
bsYear2=Mn[miy]+Dn[dayim-1];
if  (ss>=Ys[21]||ss<Ys[0])  bsYear=Yn[21];
function  rtnDateString(){
	html= bsDate+bsDate2+'<span class=week>' +bsWeek+'</span>'
	//return html;
	document.getElementById('lunar').innerHTML=html;
	document.getElementById('lunar2').innerHTML=bsYear+bsYear2;
	
}

