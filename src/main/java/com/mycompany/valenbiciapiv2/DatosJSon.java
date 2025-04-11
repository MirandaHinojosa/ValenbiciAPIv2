package com.mycompany.valenbiciapiv2;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.json.JSONArray;
import org.json.JSONObject;

import java.io.IOException;

/**
 *
 * @author juanc
 */
public class DatosJSon {
    private static String API_URL;
    private String datos; // para mostrar en el jTextArea los datos de las estaciones
    private String[] values; // para añadir los datos de las estaciones Valenbici a la BDD
    private int numEst;

    
    public int[] estacion_id;
    public String[] direccion;
    public int[] bicis_disponibles;
    public int[] anclajes_libres;
    public boolean[] estado_operativo;

    public String[] fecha_registro;

    public Double[] puntoA;
    public Double[] puntoB;
    
    
    public DatosJSon(int nE) {
        numEst = nE;
        datos = "";
        API_URL = "https://valencia.opendatasoft.com/api/explore/v2.1/catalog/datasets/valenbisi-disponibilitat-valenbisi-dsiponibilidad/records?f=json&location=39.46447,-0.39308&distance=10&limit=" + nE;
        values = new String[numEst];
        for (int i = 0; i < numEst; i++) {
            values[i] = "";
        }
    }

    public void mostrarDatos(int nE) {
        
        estacion_id=  new int[nE];
        direccion=  new String[nE];
        bicis_disponibles=  new int[nE];
        anclajes_libres=  new int[nE];
        estado_operativo=  new boolean[nE];
       
        fecha_registro=  new String[nE];
        
        puntoA=  new Double[nE];
        puntoB=  new Double[nE];
        
        
        numEst = nE;
        datos = "";
        API_URL = "https://valencia.opendatasoft.com/api/explore/v2.1/catalog/datasets/valenbisi-disponibilitat-valenbisi-dsiponibilidad/records?f=json&location=39.46447,-0.39308&distance=10&limit=" + nE;
        values = new String[numEst];
        for (int i = 0; i < numEst; i++) {
            values[i] = "";
        }

        if (API_URL.isEmpty()) {
            setDatos(getDatos().concat("La URL de la API no está especificada."));
            return;
        }

        try (CloseableHttpClient httpClient = HttpClients.createDefault()) {
            HttpGet request = new HttpGet(API_URL);
            HttpResponse response = httpClient.execute(request);
            HttpEntity entity = response.getEntity();
            
            if (entity != null) {
                String result = EntityUtils.toString(entity);
                // Procesar la respuesta como JSON
                try {
                    JSONObject jsonObject = new JSONObject(result);
                    JSONArray resultsArray = jsonObject.getJSONArray("results");
                    
                    // Procesar cada estación
                    for (int i = 0; i < resultsArray.length(); i++) {
                        JSONObject estacion = resultsArray.getJSONObject(i);
                        
                        int numero = estacion.getInt("number");
                        estacion_id[i]= estacion.getInt("number");
                        
                        String direccion1 = estacion.getString("address");
                        direccion[i] = estacion.getString("address");
                        
                        
                        int bicisDisponibles = estacion.getInt("available");
                        bicis_disponibles[i] = estacion.getInt("available");
                        
                        
                        int puestosDisponibles = estacion.getInt("free");
                        anclajes_libres[i] = estacion.getInt("free");
                         
//                        boolean estado = estacion.getBoolean("ticket");         //lo comento porque no lo muestro en el formulario
                        String valor= estacion.getString("open");
                        if(valor=="T"){
                            
                            estado_operativo[i]=true;
                        }
                        else{
                            estado_operativo[i]=true;
                        }
                        fecha_registro[i]= estacion.getString("updated_at");
                        
                        JSONObject geopoint = estacion.getJSONObject("geo_point_2d");
                        
                        puntoA[i]= geopoint.getDouble("lon");
                        puntoB[i]= geopoint.getDouble("lat");
                        
                        // Construir string para mostrar
                        String infoEstacion = String.format("Estación %d: %s - Bicis: %d, Puestos: %d%n", 
                                numero, direccion1, bicisDisponibles, puestosDisponibles);
                        datos = datos.concat(infoEstacion);
                        
                        // Guardar datos para la BDD
                        if (i < values.length) {
                            values[i] = String.format("%d,%s,%d,%d", 
                                    numero, direccion1, bicisDisponibles, puestosDisponibles);
                        }
                    }
                } catch (org.json.JSONException e) {
                    setDatos(getDatos().concat("Error al procesar los datos JSON: " + e.getMessage()));
                }
            }
        } catch (IOException e) {
            setDatos(getDatos().concat("Error de conexión: " + e.getMessage()));
        }
    }

    public String getDatos() {
        return datos;
    }

    public void setDatos(String datos) {
        this.datos = datos;
    }

    public String[] getValues() {
        return values;
    }

    public void setValues(String[] values) {
        this.values = values;
    }

    public int getNumEst() {
        return numEst;
    }

    public void setNumEst(int numEst) {
        this.numEst = numEst;
    }
}