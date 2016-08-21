package net.solidbytes.jukebox;

import java.util.List;

import net.solidbytes.jukebox.nodes.Node_Album;
import net.solidbytes.jukebox.nodes.Node_Artist;

import android.app.Activity;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ListAdapter;
import android.widget.TextView;

public class LA_Artists extends ArrayAdapter<Node_Artist> {
	
	public LA_Artists(Activity activity, List<Node_Artist> lArtists) {
		super(activity, 0, lArtists);
	}
	
	@Override
	public View getView(int position, View convertView, ViewGroup parent) {

		Activity activity = (Activity) getContext();
		LayoutInflater inflater = activity.getLayoutInflater();

		// Inflate the views from XML
		View vRow = inflater.inflate(R.layout.listentry_artist, null);
		TextView tvName = (TextView) vRow.findViewWithTag("ArtistName");
		
		Node_Artist oCurrent = getItem(position);
		tvName.setText(oCurrent.getProperty("label"));
		
		return vRow;

	}
	
}
