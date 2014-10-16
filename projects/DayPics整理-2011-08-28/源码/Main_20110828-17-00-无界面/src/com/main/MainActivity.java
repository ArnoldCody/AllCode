package com.main;

import java.io.FileInputStream;
import java.io.FileNotFoundException;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.ContentResolver;
import android.content.ContentValues;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.hardware.Camera;
import android.hardware.Camera.PictureCallback;
import android.hardware.Camera.ShutterCallback;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.util.Log;
import android.view.KeyEvent;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.webkit.WebChromeClient;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.Toast;

public class MainActivity extends Activity {
	private ImageButton webBtn;
	private ImageButton photoBtn;
	private ImageButton galleryBtn;
	private WebView webView;
	private String phoneUrl;
	private String uploadUrl;
	private Intent tmpIntent;
	private Uri tmpUri;

	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		//�����������
		ConnectivityManager conManager = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
		NetworkInfo networkInfo = conManager.getActiveNetworkInfo();    
		if (networkInfo == null || !conManager.getBackgroundDataSetting()) {
			Toast.makeText(getApplicationContext(), "���������ӣ�ǿ���˳����򣬲�����˼...", Toast.LENGTH_LONG).show();
			finish();
			//System.exit(0);
			return;      
		 } 

		//��ʼ��URL
		uploadUrl = getString(R.string.domain_name) + getString(R.string.upload_action);
		phoneUrl = getString(R.string.domain_name) + getString(R.string.phone_action);
		// ��ͼƬuriת��Ϊ·��
		Intent intent = getIntent();
		Bundle extras = intent.getExtras();
		String action = intent.getAction();

		// if this is from the share menu
		if (Intent.ACTION_SEND.equals(action)) {
			setContentView(R.layout.photo);
			if (extras.containsKey(Intent.EXTRA_STREAM)) {
				// Get resource path from intent callee
				tmpUri = (Uri) extras.getParcelable(Intent.EXTRA_STREAM);
				ContentResolver cr = this.getContentResolver();
				Cursor cursor = cr.query(tmpUri, null, null, null, null);
				cursor.moveToFirst();
				String uploadFile = cursor.getString(1);

				if (!uploadFile.equals("")) {
					// �ڽ�����ʾͼƬ
					ImageView myImg = (ImageView) findViewById(R.id.myImage);
					Bitmap bitmap = getLoacalBitmap(uploadFile); // �ӱ���ȡͼƬ
					myImg.setImageBitmap(bitmap);
					/* �趨mButton��onClick�¼����� */
					Button mButton = (Button) findViewById(R.id.upload);
					mButton.setOnClickListener(new View.OnClickListener() {
						public void onClick(View v) {
							Upload.upload(tmpUri, uploadUrl, MainActivity.this);
							setContentView(R.layout.main);
							// ���˵���ť�¼���
							bindImgBtnAction();
						}
					});
				}
			} else if (extras.containsKey(Intent.EXTRA_TEXT)) {
				return;
			}
		} else {
			setContentView(R.layout.main);
			// ���˵���ť�¼���
			bindImgBtnAction();
		}
	}

	// ��ʾ����ͼƬ
	public Bitmap getLoacalBitmap(String url) {
		try {
			FileInputStream fis = new FileInputStream(url);
			return BitmapFactory.decodeStream(fis);
		} catch (FileNotFoundException e) {
			e.printStackTrace();
			return null;
		}
	}

	// ���˵���ť�¼���
	public void bindImgBtnAction() {
		/* �趨WebBtn��onClick�¼����� */
		webBtn = (ImageButton) findViewById(R.id.WebBtn);
		webBtn.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {
				setContentView(R.layout.web);
				webView = (WebView) findViewById(R.id.webView);
				webView.getSettings().setJavaScriptEnabled(true);

				webView.setWebViewClient(new WebViewClient() {
					// ��д�˷������������ҳ��������ӻ����ڵ�ǰ��webview����ת��������������Ǳ�
					@Override
					public boolean shouldOverrideUrlLoading(WebView view,
							String url) {
						view.loadUrl(url);
						return true;
					}

					// @Override
					// public void onReceivedSslError(WebView view,
					// SslErrorHandler handler,
					// android.net.http.SslError error) { //
					// ��д�˷���������webview����https����
					// handler.proceed();
					// }
				});
				webView.setWebChromeClient(new WebChromeClient() {
					// ����������activity�ı���
					@Override
					public void onProgressChanged(WebView view, int newProgress) {
						setTitle("DayPics - " + newProgress + "%");
					}
				});

				webView.loadUrl(phoneUrl);
			}
		});
		
		/* �趨photoBtn��onClick�¼����� */
		photoBtn = (ImageButton) findViewById(R.id.PhotoBtn);
		photoBtn.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {
				setContentView(R.layout.photo);
				takePhoto();
			}
		});

		/* �趨galleryBtn��onClick�¼����� */
		galleryBtn = (ImageButton) findViewById(R.id.GalleryBtn);
		galleryBtn.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {
				setContentView(R.layout.photo);
				openGallery();
			}
		});
	}
	
	//���������
	public void takePhoto() {
		Log.d("ANDRO_CAMERA", "Starting camera on the phone...");
		String fileName = "testphoto.jpg";
		ContentValues values = new ContentValues();
		values.put(MediaStore.Images.Media.TITLE, fileName);
		values.put(MediaStore.Images.Media.DESCRIPTION,
				"Image capture by camera");
		values.put(MediaStore.Images.Media.MIME_TYPE, "image/jpeg");
		tmpUri = getContentResolver().insert(
				MediaStore.Images.Media.EXTERNAL_CONTENT_URI, values);
		Intent intent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
		intent.putExtra(MediaStore.EXTRA_OUTPUT, tmpUri);
		intent.putExtra(MediaStore.EXTRA_VIDEO_QUALITY, 1);
		startActivityForResult(intent, 2);
	}

	//��ý���
	private void openGallery() {
		Intent intent = new Intent();
		/* Open the page of select pictures and set the type to image */
		intent.setType("image/*");
		intent.setAction(Intent.ACTION_GET_CONTENT);
		startActivityForResult(intent, 1);
	}
	
	
	@Override
	protected void onActivityResult(int requestCode, int resultCode, Intent data) {
		//��Ӧý�����Ƭѡ��
		if (resultCode == RESULT_OK && requestCode == 1) {
			tmpIntent = data;
			tmpUri = tmpIntent.getData();
			Log.e("uri", tmpUri.toString());
			ContentResolver cr = this.getContentResolver();
			try {
				Bitmap bitmap = BitmapFactory.decodeStream(cr
						.openInputStream(tmpUri));
				ImageView imageView = (ImageView) findViewById(R.id.myImage);
				/* ��Bitmap�趨��ImageView */
				imageView.setImageBitmap(bitmap);
				photoUpload();
			} catch (FileNotFoundException e) {
				Log.e("Exception", e.getMessage(), e);
			}
		}
		super.onActivityResult(requestCode, resultCode, data);
		
		//��Ӧ���ն���
		if (requestCode == 2 && resultCode == RESULT_OK) {
			ImageView imageView = (ImageView) findViewById(R.id.myImage);
			imageView.setImageURI(tmpUri);
			photoUpload();
		}
	}
	
	//�󶨰�ť�ϴ�ͼƬ
	public void photoUpload()
	{
		/* �趨mButton��onClick�¼����� */
		Button mButton = (Button) findViewById(R.id.upload);
		mButton.setOnClickListener(new View.OnClickListener() {
			public void onClick(View v) {
				Upload.upload(tmpUri, uploadUrl, MainActivity.this);
				setContentView(R.layout.main);
				bindImgBtnAction();
			}
		});		
	}
		
	//���˼�����
	@Override
	public void onBackPressed() {
		//��webView���Ϊ��ҳ���˼�
		if (findViewById(R.id.webView) != null && webView.canGoBack()) {
			webView.goBack(); // goBack()��ʾ����webView����һҳ��
			return;
		}
		//���������˳�����
		if (findViewById(R.id.GalleryBtn) != null) {
			quit();
			return;
		}
		
		//���ϴ�ҳ���˻�������
		if (findViewById(R.id.upload) != null) {
			setContentView(R.layout.main);
			bindImgBtnAction();			
		}
		return;
	}
	
	//�˳�����
	public void quit()
	{
		new AlertDialog.Builder(this)
		.setTitle("DayPics")
		.setMessage("ȷ���˳�?")
		.setIcon(android.R.drawable.ic_dialog_info)
		.setPositiveButton("ȷ��",
				new DialogInterface.OnClickListener() {
					public void onClick(DialogInterface dialog,
							int whichButton) {
						setResult(RESULT_OK);// ȷ����ť�¼�
						System.exit(0);
					}
				})
		.setNegativeButton("ȡ��",
				new DialogInterface.OnClickListener() {
					public void onClick(DialogInterface dialog,
							int whichButton) {
						// ȡ����ť�¼�
					}
				}).show();		
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		int i = ActionMenu.selectItem(item);
		switch (i) {
		case 1:
			setContentView(R.layout.main);
			bindImgBtnAction();
			break;
		case 2:
			break;
		case 3:
			quit();
			break;
		case 4:
			webView.reload();
			break;
		case 5:
			webView.goBack();
			break;
		}
		return false;
	}
	
	
	@Override
	public boolean onPrepareOptionsMenu(Menu menu) {
		menu.clear();
		if(findViewById(R.id.webView)!=null)
		{
			ActionMenu.setMenuItemWebApp(menu);
		}
		else
		{
			ActionMenu.setMenuItem(menu);
		}
		// �������false���˷����Ͱ��û����menu�Ķ����������ˣ�onCreateOptionsMenu���������ᱻ����
		return true;
	}
}