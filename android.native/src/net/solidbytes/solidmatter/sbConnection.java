package net.solidbytes.solidmatter;

import net.solidbytes.jukebox.R;
import net.solidbytes.tools.App;
import net.solidbytes.tools.Logg;
import net.solidbytes.tools.Stopwatch;

import java.io.*;
import java.net.*;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.w3c.dom.Document;

import android.content.Context;
import android.content.SharedPreferences;
import android.preference.PreferenceManager;
import android.util.Log;
import android.widget.Toast;

public abstract class sbConnection {

	private static int		iTimeout	= 2000;

	private static String	sDomain;
	private static String	sUser;
	private static String	sPass;

	private static String	sSessionID;

	private static String	sCookie		= "";

	public static boolean	bConnected	= false;
	public static String	sError		= "";

	public static boolean connect() {
		return connect(false);
	}

	/**
	 * @param bForceReconnect
	 * @return
	 */
	public static boolean connect(Boolean bForceReconnect) {

		if (bForceReconnect == null) {
			bForceReconnect = false;
		}

		sDomain = App.Prefs.getString("server_domain", "ollomulder.dyndns.org");
		sUser = App.Prefs.getString("server_username", "ollo");
		sPass = App.Prefs.getString("server_password", "test");

		// sDomain = "ollomulder.dyndns.org";
		// sDomain = "192.168.100.33";
		// sDomain = "10.7.12.138";

		// don't reconnect if already connected
		if (isConnected() && bForceReconnect == false) {
			return true;
		}

		boolean bSuccess = login();

		if (!bSuccess) {
			Toast toast = Toast.makeText(App.Context, "Could not connect to server. \n" + sbConnection.sError, Toast.LENGTH_LONG);
			toast.show();
		} else {
			//Toast toast = Toast.makeText(App.Context, "Connected to " + App.Prefs.getString("server_domain", ""), Toast.LENGTH_SHORT);
			//toast.show();
		}

		return bSuccess;

	}

	/**
	 * @return
	 */
	public static String getDomain() {
		return sDomain;
	}

	/**
	 * @return
	 */
	public static String getSessionID() {
		return sSessionID;
	}

	/**
	 * @return
	 */
	public static boolean login() {

		// connect to server
		// SharedPreferences prefs =
		// PreferenceManager.getDefaultSharedPreferences(getBaseContext());

		boolean bSuccess = true;

		try {

			String sAction = "/-/login/login/";
			String sPost = "login=" + sUser + "&password=" + sPass;

			if (sDomain == "") {
				throw new Exception("no server domain specified");
			}

			Log.i("sbTools", "sbConnection: attempting login at " + sDomain);

			sbDOMResponse domResponse = sendRequest(sAction, sPost);

			if (domResponse.getUserID() == null) {
				bSuccess = false;
				Log.i("sbTools", "sbConnection: Login failed, no UserID returned");
			} else {
				sSessionID = domResponse.getSessionID();
				Log.i("sbTools", "sbConnection: Login successful, SessionID: " + sSessionID);
			}

		} catch (UnknownHostException e) {

			bSuccess = false;
			sbConnection.sError = "Error: " + R.string.error_unknown_host + "(" + sDomain + ")";

		} catch (Exception e) {

			bSuccess = false;
			Logg.e("sbTools", e);
			sbConnection.sError = "Error: " + e;

		}

		return bSuccess;

	}

	/**
	 * @param sAction
	 * @return
	 * @throws Exception
	 */
	public static sbDOMResponse sendRequest(String sAction) throws Exception {
		return sendRequest(sAction, null);
	}

	/**
	 * @param sAction
	 * @param sPostData
	 * @return
	 * @throws Exception
	 */
	public static sbDOMResponse sendRequest(String sAction, String sPostData) throws Exception {

		InputStream is = null;

		Stopwatch tCon = new Stopwatch();
		Log.d("sbTools", "sbConnection: sending request: " + sAction);

		try {

			is = getStream(sAction, sPostData);

			// Receive response document
			DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
			DocumentBuilder builder = factory.newDocumentBuilder();
			Document domResponse = builder.parse(is);

			Log.d("sbTools", "sbConnection: HTTP request and parsing took " + tCon.Stop() + " ms");

			// output for debugging purposes
			Transformer transformer = TransformerFactory.newInstance().newTransformer();
			transformer.setOutputProperty(OutputKeys.INDENT, "yes");

			// initialize StreamResult with File object to save to file
			StreamResult result = new StreamResult(new StringWriter());
			DOMSource source = new DOMSource(domResponse);
			transformer.transform(source, result);

			String xmlString = result.getWriter().toString();
			Log.v("sbTools", xmlString);

			return (new sbDOMResponse(domResponse));

		} catch (UnknownHostException e) {
			
			Logg.e("sbTools", e);
			sbConnection.sError = "Error: unknown host (" + sDomain + ")";
			disconnect();
			return null;
			
		} catch (SocketTimeoutException e) {
			
			Logg.e("sbTools", e);
			sbConnection.sError = "Error: server not responding (" + sDomain + ")";
			disconnect();
			return null;
			
		} catch (Exception e) {
			
			Logg.e("sbTools", e);
			sbConnection.sError = "Error: " + e;
			disconnect();
			return null;
			
		} finally {

			// if( os != null ) try { os.close(); } catch( IOException ex )
			// {/*ok*/}
			if (is != null)
				try {
					is.close();
				} catch (IOException ex) {/* ok */
				}

		}

	}

	/**
	 * @param sAction
	 * @param sPostData
	 * @return
	 * @throws Exception
	 */
	public static InputStream getStream(String sAction, String sPostData) throws Exception {

		Log.d("sbTools", "sbConnection: opening stream ressource");

		try {

			URLConnection con = getConnection(sAction, sPostData);
			con.connect();
			InputStream is = con.getInputStream();

			return (is);

		} catch (Exception e) {

			Logg.e("sbTools", e);
			sbConnection.sError = "Error: " + e;
			throw e;

		}

	}

	/**
	 * @param sAction
	 * @param sPostData
	 * @return
	 * @throws Exception
	 */
	public static URLConnection getConnection(String sAction, String sPostData) throws Exception {

		OutputStream os = null;

		String sURL = "http://" + sDomain + "/api" + sAction;

		Log.d("sbTools", "opening sbConnection: " + sURL);

		try {

			// Connection:
			URL urlRequest = new URL(sURL);
			URLConnection con = urlRequest.openConnection();

			// add session cookie if logged in
			if (isConnected()) {
				String sCookie = "PHPSESSID=" + sSessionID;
				con.setRequestProperty("Cookie", sCookie);
			}

			con.setConnectTimeout(iTimeout);
			if (!(con instanceof HttpURLConnection)) {
				throw new Exception("Error: Only HTTP allowed.");
			}
			((HttpURLConnection) con).setRequestMethod("POST");
			con.setDoOutput(true);

			// Send data:
			os = con.getOutputStream();
			if (sPostData != null && sPostData != "") {
				os.write(sPostData.getBytes());
			}
			os.flush();
			con.connect();

			return (con);

		} catch (Exception e) {

			Logg.e("sbTools", e);
			sbConnection.sError = "Error: " + e;
			throw e;

		} finally {

			if (os != null)
				try {
					os.close();
				} catch (IOException ex) {/* ok */
				}

		}

	}

	/**
	 * @return
	 */
	public static boolean isConnected() {
		
		if (sSessionID != null) {
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 * @return
	 */
	public static boolean disconnect() {
		
		sSessionID = null;
		sCookie = "";
		bConnected = false;
		
		return (true);
		
	}

}