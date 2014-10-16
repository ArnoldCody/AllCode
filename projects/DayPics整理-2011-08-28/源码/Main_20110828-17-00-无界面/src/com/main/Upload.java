package com.main;

import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.FileInputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;

import android.app.Activity;
import android.content.ContentResolver;
import android.content.Intent;
import android.database.Cursor;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.widget.EditText;
import android.widget.Toast;
import biz.source_code.base64Coder.Base64Coder;

public class Upload extends Activity {
	public static void upload(Uri uri, String action, Activity act)
    {
      String end = "\r\n";
      String twoHyphens = "--";
      String boundary = "*****";
      try
      {
        URL url = new URL(action);
        HttpURLConnection con = (HttpURLConnection) url.openConnection();
        /* ����Input��Output����ʹ��Cache */
        con.setDoInput(true);
        con.setDoOutput(true);
        con.setUseCaches(false);
        /* �趨���͵�method=POST */
        con.setRequestMethod("POST");
        /* setRequestProperty */
        con.setRequestProperty("Connection", "Keep-Alive");
        con.setRequestProperty("Charset", "UTF-8");
        con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;boundary=" + boundary);
        /* �趨DataOutputStream */
        DataOutputStream ds = new DataOutputStream(con.getOutputStream());
                
        //�����Լ����ļ�ͷ������b@_@b�ָ�
        EditText et = (EditText)act.findViewById(R.id.words);
        String words = Base64Coder.encodeString(et.getText().toString());
        ds.writeBytes(words + "b@_@b");
        
        /* ȡ���ļ���FileInputStream */
       	ContentResolver cr = act.getContentResolver();
    	Cursor cursor = cr.query(uri, null, null, null, null);
    	cursor.moveToFirst();
    	String uploadFile = cursor.getString(1);
        FileInputStream fStream = new FileInputStream(uploadFile);
        /* �趨ÿ��д��1024bytes */
        int bufferSize = 1024;
        byte[] buffer = new byte[bufferSize];

        int length = -1;
        /* ���ļ���ȡ���ݵ������� */
        while ((length = fStream.read(buffer)) != -1)
        {
          /* ������д��DataOutputStream�� */
          ds.write(buffer, 0, length);
        }
        ds.writeBytes(end);
        ds.writeBytes(twoHyphens + boundary + twoHyphens + end);

        /* close streams */
        fStream.close();
        ds.flush();

        /* ȡ��Response���� */
        InputStream is = con.getInputStream();
        StringBuffer b = new StringBuffer();
        BufferedReader br = new BufferedReader(  
                new InputStreamReader(is,"UTF-8"));  
        String data = "";  

        while ((data = br.readLine()) != null) {  
            b.append(data); 
        }
        /* ��Response��ʾ��Dialog */
        //showDialog(b.toString().trim());
        Toast.makeText(act.getApplicationContext(), b.toString().trim(), Toast.LENGTH_LONG).show();
        /* �ر�DataOutputStream */
        ds.close();   
      }
      catch (Exception e)
      {
    	  Log.e("Exception", e.toString());
      }
    }
}
