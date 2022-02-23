let markers = [];
function initMap() {
    
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 17,
        center: { lat: -0.1801058809690268, lng: -78.48938336063523 },
    });
    map.addListener('click', (e) =>{
        const marker = new google.maps.Marker({
            position: e.latLng,
            map: map
        });
        map.setZoom(17);
        map.panTo(e.latLng);
        $("#id_latitud").val(e.latLng.toJSON().lat);
        $("#id_longitud").val(e.latLng.toJSON().lng);
    });
    const geocoder = new google.maps.Geocoder();
    document.getElementById("btn_geolocalizar").addEventListener("click", () => {
        geocodeAddress(geocoder, map);
    });
}
  
function geocodeAddress(geocoder, resultsMap) {

    var pais = document.getElementById("PAIS").value;
    var provincia = document.getElementById("PROVINCIA").value;
    var canton = document.getElementById("CANTON").value;
    var parroquia = document.getElementById("PARROQUIA").value;
    var calle_principal = document.getElementById("CALLE_PRINCIPAL").value;
    var calle_secundaria = document.getElementById("CALLE_SECUNDARIA").value;
    var numeracion = document.getElementById("NUMERACION").value;
    if (provincia=='') {
        $.notify("Ingrese provincia/estado: ","warn");
        return false;
    }
    if (canton=='') {
        $.notify("Ingrese cantón/ciudad: ","warn");
        return false;
    }
    if (calle_principal=='') {
        $.notify("Ingrese calle principal: ","warn");
        return false;
    }
    if (calle_secundaria=='') {
        $.notify("Ingrese calle secundaria: ","warn");
        return false;
    }

    const address = (pais==""?'Ecuador':pais)+(provincia==""?'':', '+provincia)+(canton==""?'':', '+canton)+(parroquia==""?'':', '+parroquia)+(calle_principal==""?'':', '+calle_principal)+(calle_secundaria==""?'':' y '+calle_secundaria)+(numeracion==""?'':', '+numeracion);
    geocoder.geocode({ address: address }, (results, status) => {
        if (status === "OK") {
            resultsMap.setZoom(17);
            resultsMap.panTo(results[0].geometry.location);
            const marker = new google.maps.Marker({
                map: resultsMap,
                position: results[0].geometry.location,
            });
            $("#LATITUD").val(results[0].geometry.location.toJSON().lat);
            $("#LONGITUD").val(results[0].geometry.location.toJSON().lng);
        } else {
            $.notify("No se ha logrado localizar la direccion ingresada: ","error");
        }
    });
}