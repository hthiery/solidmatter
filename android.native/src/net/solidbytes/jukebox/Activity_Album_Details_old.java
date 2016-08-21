package net.solidbytes.jukebox;

import java.util.List;

import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import net.solidbytes.jukebox.nodes.Node_Album;
import net.solidbytes.jukebox.nodes.Node_Track;
import net.solidbytes.solidmatter.sbConnection;
import net.solidbytes.solidmatter.sbDOMResponse;
import net.solidbytes.tools.SpinnerDialog;

import android.app.Activity;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TableLayout;
import android.widget.TextView;

public class Activity_Album_Details_old extends Activity {

	protected Node_Album nodeAlbum;
	
	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {

		super.onCreate(savedInstanceState);
		
//		this.icon.setBackgroundResource(R.drawable.ic_header_albums);
		
		//setContentView(R.layout.album_details);
		
		
		//setTitle("Album - Detail");
		
		
		SpinnerDialog sdWait = new SpinnerDialog(this);
		sdWait.show();
		
		
		String sUUID = "";
		Bundle extras = getIntent().getExtras();
		if(extras !=null) {
			sUUID = extras.getString("album_uuid");
		}
		
		try {
			
			sbDOMResponse domResponse = sbConnection.sendRequest("/" + sUUID);
			NodeList nodes = domResponse.getElementsByXPath("//sbnode[@master='true']");
			
	        nodeAlbum = new Node_Album((Element) nodes.item(0));
	        setContentView(nodeAlbum.getView(Node_Album.DETAILS));
	        
		} catch (Exception e) {
			
			Log.e("sbJukebox", "Error rendering album >> " + e.getMessage() + " // " + e.toString());
            throw new RuntimeException(e);
			
		}
		
		sdWait.hide();
		
	}


}