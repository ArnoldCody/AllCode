package com.main;

import android.app.Activity;
import android.view.Menu;
import android.view.MenuItem;
import android.webkit.WebView;
import android.widget.Toast;

public class ActionMenu extends Activity {
	public static void setMenuItemWebApp(Menu menu) {
		/*
		 * add()�������ĸ������������ǣ�
		 * 1��������������Ļ���дMenu.NONE,
		 * 2��Id���������Ҫ��Android�������Id��ȷ����ͬ�Ĳ˵�
		 * 3��˳���Ǹ��˵�������ǰ������������Ĵ�С����
		 * 4���ı����˵�����ʾ�ı�
		 */
		menu.add(Menu.NONE, Menu.FIRST + 1, 3, "���˵�").setIcon(android.R.drawable.ic_menu_delete);
		// setIcon()����Ϊ�˵�����ͼ�꣬����ʹ�õ���ϵͳ�Դ���ͼ�꣬ͬѧ������һ��,��
		// android.R��ͷ����Դ��ϵͳ�ṩ�ģ������Լ��ṩ����Դ����R��ͷ��
		menu.add(Menu.NONE, Menu.FIRST + 2, 4, "����").setIcon(android.R.drawable.ic_menu_set_as);
		menu.add(Menu.NONE, Menu.FIRST + 3, 5, "�˳�����").setIcon(android.R.drawable.ic_menu_edit);
		menu.add(Menu.FIRST, Menu.FIRST + 4, 1, "ˢ��").setIcon(android.R.drawable.ic_menu_delete);
		menu.add(Menu.FIRST, Menu.FIRST + 5, 2, "����").setIcon(android.R.drawable.ic_menu_delete);
	}

	public static int selectItem(MenuItem item) {
		switch (item.getItemId()) {
		case Menu.FIRST + 1:
			return 1;
		case Menu.FIRST + 2:
			return 2;
		case Menu.FIRST + 3:
			return 3;
		case Menu.FIRST + 4:
			return 4;
		case Menu.FIRST + 5:
			return 5;
		}
		return 0;
	}
	
	public static void setMenuItem(Menu menu) {
		menu.add(Menu.NONE, Menu.FIRST + 1, 1, "���˵�").setIcon(android.R.drawable.ic_menu_delete);
		menu.add(Menu.NONE, Menu.FIRST + 2, 2, "����").setIcon(android.R.drawable.ic_menu_set_as);
		menu.add(Menu.NONE, Menu.FIRST + 3, 3, "�˳�����").setIcon(android.R.drawable.ic_menu_edit);
	}
}
