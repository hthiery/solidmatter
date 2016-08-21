package net.solidbytes.jukebox.nodes;

import java.util.ArrayList;
import java.util.List;

import net.solidbytes.jukebox.Activity_Album_Details;
import net.solidbytes.jukebox.R;
import net.solidbytes.solidmatter.sbConnection;
import net.solidbytes.solidmatter.sbNode;
import net.solidbytes.tools.App;

import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import android.app.Activity;
import android.content.Intent;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.ImageView;
import android.widget.TextView;

public class Node_Artist extends sbNode {
	
	public static final int ROW = 1;
	
	protected List<Node_Album>				lAlbums	= new ArrayList<Node_Album>();
	
	public Node_Artist(Element eArtist) {
		
		hmProperties.put("uuid", "");
		hmProperties.put("name", "");
		hmProperties.put("label", "");
		
		super.fillByElement(eArtist);
		
	}
	
	protected int getIconID() {
		return R.drawable.ic_type_artist;
	}
	
	public View getView(int iViewVariant) {
		
		String sArtist = this.getProperty("label");
		
		switch (iViewVariant) {
		
		case 1: // ROW
			
			// Inflate the views from XML
			View vRow = App.inflate(R.layout.listentry_artist, null);
			TextView tvName = (TextView) vRow.findViewWithTag("ArtistName");
			
			tvName.setText(sArtist);
			
			return vRow;
			
		default:
			return super.getView(iViewVariant);
			
		}
		
	}
	
	
	/**
	 * @return
	 */
	public List<Node_Album> getAlbums() {
		
		NodeList nlTracks = super.getNodesByXPath("children[@mode='albums']/sbnode");

		Log.d("sbJukebox", "found " + nlTracks.getLength() + " Tracks");

		for (int i = 0; i < nlTracks.getLength(); i++) {
			Element eCurrent = (Element) nlTracks.item(i);
			Node_Album aCurrent = new Node_Album(eCurrent);
			lAlbums.add(aCurrent);
			Log.d("sbJukebox", "found album: " + aCurrent.getProperty("label"));
		}

		return lAlbums;

	}
	
}