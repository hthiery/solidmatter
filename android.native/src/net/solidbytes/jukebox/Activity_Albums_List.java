package net.solidbytes.jukebox;

import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathExpression;
import javax.xml.xpath.XPathFactory;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import net.solidbytes.jukebox.nodes.Node_Album;
import net.solidbytes.jukebox.nodes.Node_Jukebox;
import net.solidbytes.solidmatter.sbConnection;
import net.solidbytes.solidmatter.sbDOMResponse;
import net.solidbytes.solidmatter.sbNode;
import net.solidbytes.tools.App;
import android.app.Activity;
import android.app.Dialog;
import android.app.ListActivity;
import android.app.ProgressDialog;
import android.content.Intent;
import android.content.res.Resources;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.view.ContextMenu;
import android.view.ContextMenu.ContextMenuInfo;
import android.view.MenuItem;
import android.view.View;
import android.widget.AdapterView;
import android.widget.AdapterView.AdapterContextMenuInfo;
import android.widget.AdapterView.OnItemLongClickListener;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.AdapterView.OnItemClickListener;

public class Activity_Albums_List extends sbJukeboxListActivity {

	List<Node_Album>	lAlbums	= new ArrayList<Node_Album>();

	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {

		super.onCreate(savedInstanceState);

		this.icon.setBackgroundResource(R.drawable.ic_header_albums);
		
		

	}

	@Override 
	protected void onStart() {
		
		super.onStart();
		
		String sShow = "";
		Bundle extras = getIntent().getExtras();
		if (extras != null) {
			sShow = extras.getString("show");
		}

		try {
			
//			ProgressDialog pdSpinner = new ProgressDialog(getApplicationContext());
//			pdSpinner.setProgressStyle(ProgressDialog.STYLE_SPINNER);
//			pdSpinner.show();
			
			if (sShow == "" || sShow == null) {
				lAlbums = Node_Jukebox.getAlbums(null);
				this.title.setText(R.string.labels_random_albums);
			} else {
				lAlbums = Node_Jukebox.getAlbums(sShow);
				this.title.setText(R.string.labels_albums_beginning_with + sShow);
			}
			
//			pdSpinner.dismiss();
			
//			if (lAlbums.isEmpty()) {
//				setContentView(R.layout.no_content);
//				return;
//			}
			
			setListAdapter(new LA_Albums(this, lAlbums));
			
			ListView lv = getListView();
			lv.setTextFilterEnabled(true);

			lv.setOnItemClickListener(new OnItemClickListener() {

				public void onItemClick(AdapterView<?> parent, View view, int position, long id) {
					Intent myIntent = new Intent(view.getContext(), Activity_Album_Details.class);
					myIntent.putExtra("album_uuid", lAlbums.get(position).getProperty("uuid"));
					startActivityForResult(myIntent, 0);
				}

			});

			registerForContextMenu(lv);
			
			// initialize download dialog
			

			
			
			// lv.setOnItemLongClickListener(new OnItemLongClickListener() {
			//
			// public boolean onItemLongClick(AdapterView<?> parent, View view,
			// int position, long id) {
			// Intent myIntent = new Intent(view.getContext(),
			// Activity_Album_Details.class);
			// myIntent.putExtra("album_uuid",
			// lAlbums.get(position).getProperty("uuid"));
			// startActivityForResult(myIntent, 0);
			// return false;
			// }
			//
			// });
			
			
			

		} catch (Exception e) {

			Log.e("sbJukebox", "Error rendering album list >> " + e.getMessage() + " // " + e.toString());
			// throw new RuntimeException(e);

		}
		
	};
	
	
	@Override
	public void onCreateContextMenu(ContextMenu menu, View v, ContextMenuInfo menuInfo) {

		super.onCreateContextMenu(menu, v, menuInfo);

		AdapterContextMenuInfo info = (AdapterContextMenuInfo) menuInfo;

		sbNode nodeAlbum = (sbNode) info.targetView.getTag(R.id.sbNode);

		menu.setHeaderTitle(nodeAlbum.getProperty("label"));
		menu.add(0, R.id.download, 0, "Download");
		menu.add(0, R.id.play, 0, "Play");

	}

	@Override
	public boolean onContextItemSelected(MenuItem item) {
		
		AdapterContextMenuInfo info = (AdapterContextMenuInfo) item.getMenuInfo();
		
		Node_Album nodeAlbum = (Node_Album) info.targetView.getTag(R.id.sbNode);
		
		switch (item.getItemId()) {
		case R.id.download:
			new AsyncTask_DownloadAlbum((Activity) this).execute(nodeAlbum);
			//nodeAlbum.download();
			return true;
		case R.id.play:
//			String sURL = "http://" + sbConnection.getDomain() + "/" + nodeAlbum.getProperty("uuid") + "/details/getM3U/playlist.m3u/?sid=" + sbConnection.getSessionID();
//			Intent i = new Intent(android.content.Intent.ACTION_VIEW);
//			i.setDataAndType(Uri.parse(sURL), "*/*");
//			App.Context.startActivity(i);
			String sURI = nodeAlbum.getPlaylist();
			Intent i = new Intent(android.content.Intent.ACTION_VIEW);
			i.setDataAndType(Uri.parse(sURI), "audio/x-mpegurl");
			App.Context.startActivity(i);
			return true;
		default:
			return super.onContextItemSelected(item);
		}
	}
	
//	protected Dialog onCreateDialog(int id) {
//        switch(id) {
//        case PROGRESS_DIALOG:
//            progressDialog = new ProgressDialog(NotificationTest.this);
//            progressDialog.setProgressStyle(ProgressDialog.STYLE_HORIZONTAL);
//            progressDialog.setMessage("Loading...");
//            return progressDialog;
//        default:
//            return null;
//        }
//    }
//
//    @Override
//    protected void onPrepareDialog(int id, Dialog dialog) {
//        switch(id) {
//        case PROGRESS_DIALOG:
//            progressDialog.setProgress(0);
//            progressThread = new ProgressThread(handler);
//            progressThread.start();
//    }
//
//    // Define the Handler that receives messages from the thread and update the progress
//    final Handler handler = new Handler() {
//        public void handleMessage(Message msg) {
//            int total = msg.arg1;
//            progressDialog.setProgress(total);
//            if (total >= 100){
//                dismissDialog(PROGRESS_DIALOG);
//                progressThread.setState(ProgressThread.STATE_DONE);
//            }
//        }
//    };
//
//    /** Nested class that performs progress calculations (counting) */
//    private class ProgressThread extends Thread {
//        Handler mHandler;
//        final static int STATE_DONE = 0;
//        final static int STATE_RUNNING = 1;
//        int mState;
//        int total;
//       
//        ProgressThread(Handler h) {
//            mHandler = h;
//        }
//       
//        public void run() {
//            mState = STATE_RUNNING;   
//            total = 0;
//            while (mState == STATE_RUNNING) {
//                try {
//                    Thread.sleep(100);
//                } catch (InterruptedException e) {
//                    Log.e("ERROR", "Thread Interrupted");
//                }
//                Message msg = mHandler.obtainMessage();
//                msg.arg1 = total;
//                mHandler.sendMessage(msg);
//                total++;
//            }
//        }
//        
//        /* sets the current state for the thread,
//         * used to stop the thread */
//        public void setState(int state) {
//            mState = state;
//        }
//    }


}