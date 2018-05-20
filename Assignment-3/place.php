<?php
if(isset($_POST['action']))
{
	$key="AIzaSyCje8dLBNMjoTWeJkIMnfXz8-bd_QxoRpU";
	switch($_POST['action'])
	{
		case "form":
			$distance=$_POST['distance'];
			if($distance==="")
				$distance=10;
			$radius= $distance * 1609.344;
  			if($_POST['loc']=='here')
			{
				$lat=$_POST['lat'];
				$lon=$_POST['lon'];
			}

			else
			{
				$location=$_POST['location'];
				$resultLocation=file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($location)."&key=$key");
				$resultLocation=json_decode($resultLocation);
				$resultLocation=$resultLocation->results[0]->geometry->location;
				$lat=$resultLocation->lat;
				$lon=$resultLocation->lng;

			}
			$url="https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=".urlencode($lat.", ".$lon)."&radius=".$radius."&type=".$_POST['category']."&keyword=".urlencode($_POST['keyword'])."&key=$key";
			$result= file_get_contents($url);
			$result=json_decode($result);
			$result->lat=$lat;
			$result->lon=$lon;
			echo json_encode($result);
		break;

		case "place":
			$result= file_get_contents("https://maps.googleapis.com/maps/api/place/details/json?placeid=$_POST[placeId]&key=$key");
			$result=json_decode($result);
			$result=$result->result;
			$pics=$result->photos;
			$reviews=$result->reviews;

			$i=0; $arrReviews=array();
			foreach($reviews as $review)
			{
				$arrReviews[]=array("text"=>$review->text, "author"=>$review->author_name, "profilePic"=>$review->profile_photo_url);
				$i++;
				if($i>5)
					break;
			}

			$i=0; $arrPics=array();
			foreach($pics as $pic)
			{
				$url="https://maps.googleapis.com/maps/api/place/photo?maxwidth=750&photoreference=".urlencode($pic->photo_reference)."&key=".urlencode($key);
				$picData=file_get_contents($url);
				file_put_contents($i.".jpg",$picData);
				$arrPics[]=$i.".jpg";
				$i++;
				if($i>4)
					break;
			}
			echo json_encode(array("name"=>$result->name,"reviews"=>$arrReviews,"pics"=>$arrPics));
		break;

	}

}
else
{

?>

<html>
<head>
<title>Homework 6</title>

<script type="application/javascript">

var loc;
function fetch(json)
{
   loc = json;
   var latitude=json.lat;
   var longitude=json.lon;

   document.getElementById("search").disabled=false;

}

 function before()
 {
	 resetMap();
	 if(!document.getElementById('keyword').reportValidity())
		return false;
	var myform=document.getElementById('myform');
	 if(document.getElementById('lochere').checked)
	 {
	   var lat = document.createElement("input");
	   var lon = document.createElement("input");
	   lat.setAttribute("type", "hidden");
	   lat.setAttribute("name", "lat");
	   lat.setAttribute("value", loc.lat);
	   lon.setAttribute("type", "hidden");
	   lon.setAttribute("name", "lon");
	   lon.setAttribute("value", loc.lon);
	   myform.appendChild(lat);
	   myform.appendChild(lon);
	 }
	 else
	 {
		if(!document.getElementById('location').reportValidity())
			return false;
	 }

   submitForm('form',new FormData (myform));

  }

function submitForm(type, data)
{
	resetMap();
	var xmlhttp=new XMLHttpRequest();
    xmlhttp.onreadystatechange = function()
    {
      if (this.readyState == 4 && this.status == 200)
      {
		  try{
			  json=JSON.parse(xmlhttp.responseText);
			 if(type=='form')
			 	jsonToTable(json);
			else if(type=='place')
				jsonToPlace(json);
		  }
		  catch(e){
			 alert(e);
			 return false;
		  }
      }
  };
    xmlhttp.open("POST",'mahima27_place.php',true);
    xmlhttp.send(data);
	//getObj('main').innerHTML='Loading...';

}

function getObj(id)
{
	return document.getElementById(id);
}

function resetMap()
{
	document.getElementById("map").innerHTML="";
	document.getElementById("map").style.display="none";
	document.getElementById("mapOptions").style.display="none";
}

function resetAll()
{
  document.getElementById("myform").reset();
  document.getElementById("main").innerHTML="";
  resetMap();
}
var data;
function jsonToTable(json)
{
	loc.lat=parseFloat(json.lat);
	loc.lon=parseFloat(json.lon);
	var records=json.results.length;
	if(records==0)
	{
		getObj('main').innerHTML='<div class="box"><strong>No Records have been found.</strong></div>';
		return;
	}
	var html='<table id="mytable" class="mytable" width="80%" border="1">';

	html+='<tr><th>Category</th><th>Name</th><th>Address</th></tr>';

	for(var i in json.results)
	{
		var icon=json.results[i].icon;
		if(icon!='') icon='<img width="40px" height="30px" src="'+icon+'" />';
		html+='<tr><td>'+icon+'</td><td onclick="placeData(\''+json.results[i].place_id+'\')">'+json.results[i].name+'</td><td onclick="showMap(this,'+json.results[i].geometry.location.lat+','+json.results[i].geometry.location.lng+')">'+json.results[i].vicinity+'</td></tr>';
	}

	html+='</table>';
	getObj('main').innerHTML='<div class="">'+html+'</div>';
}

var map;
function initMap() {
	map = new google.maps.Map(document.getElementById('map'), {
	  zoom: 4,
	});
}

var lastMapLat=0;
var lastMapLon=0;

function routing(mode) {
        var selectedMode = mode;
        directionsService.route({
          origin: {lat: loc.lat, lng: loc.lon},
          destination: {lat: lastMapLat, lng: lastMapLon},
          travelMode: google.maps.TravelMode[selectedMode]
        }, function(response, status) {
          if (status == 'OK') {
            directionsDisplay.setDirections(response);
          } else {
            window.alert('Directions request failed due to ' + status);
          }
        });
      }
function showMap(obj,lat,lon)
{
	if(lastMapLat!=lat && lastMapLon !=lon)
	{
		getObj('map').style.top=(getObj('mytable').offsetTop+obj.offsetTop+50)+'px';
		getObj('map').style.left=(getObj('mytable').offsetLeft+obj.offsetLeft+5)+'px';
		getObj('map').style.display="block";

		getObj('mapOptions').style.top=(getObj('mytable').offsetTop+obj.offsetTop +60)+'px';
		getObj('mapOptions').style.left=(getObj('mytable').offsetLeft+obj.offsetLeft+15)+'px';
		getObj('mapOptions').style.display="block";

		directionsDisplay = new google.maps.DirectionsRenderer;
    directionsService = new google.maps.DirectionsService;
		var uluru = {lat: lat, lng: lon};
		var map = new google.maps.Map(document.getElementById('map'), {
		  zoom: 12,
		  center: uluru,
		});
		directionsDisplay.setMap(map);
		//marker.setMap(null);
		var marker = new google.maps.Marker({
		  position: uluru,
		  map: map
		});
		lastMapLat=lat;
		lastMapLon=lon;
	}
	else
	{
		getObj('map').style.display="none";
		getObj('mapOptions').style.display="none"
		getObj('map').innerHTML="";
		lastMapLat=lastMapLon=0;
	}
}

function placeData(placeId)
{
	var formData = new FormData();
	formData.append("action", "place");
	formData.append("placeId", placeId);
	submitForm('place', formData)
}

function jsonToPlace(json)
{
	var html='<div><strong>'+json.name+'</strong></div><br><br>';
	html+='<div id="divShowReviews" onclick="showReview()">click to show reviews<br><div><img src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png" style="height:20px; width:30px;"></div></div><br>';
	html+='<div id="divHideReviews" onclick="hideReview()">click to hide reviews<br><div><img src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png" style="height:20px; width:30px;"></div></div>';
	html+='<div id="divReviews">';
	html+='<table id="tableReviews">';
	var img='';
	for( var x in json.reviews)
	{
		img=json.reviews[x].profilePic;
		if(img!='')
			img='<img width="50" src="'+img+'" />';
		html+='<tr><td align="center">'+img+'&nbsp;&nbsp;<strong>'+json.reviews[x].author+'</strong></td></tr>';
		html+='<tr><td colspan="2" style="padding-bottom:0px;">'+json.reviews[x].text+'</td></tr>';
	}

	html+='</table>';
	html+='<br></div>';

	html+='<div id="divShowPhotos" onclick="showPhotos()">click to show photos<br><div><img src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png" style="height:20px; width:30px;"></div></div>';
	html+='<div id="divHidePhotos" onclick="hidePhotos()">click to hide photos<br><div><img src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png" style="height:20px; width:30px;"></div></div>';
	html+='<div id="divPhotos">';
	for(var x in json.pics)
	{
		img=json.pics[x];
		if(img!='')
			img='<a href="'+img+'" target="_blank"><img width="95%" height="60%" src="'+img+'?'+Math.random() * 100000+'" /></a>';
		html+='<div class="box">'+img+'</div>';
	}

	html+='</div>';
	getObj('main').innerHTML=html;
}

function showReview()
{
	hidePhotos();
	hide('divShowReviews');
	show('divHideReviews');
	show('divReviews');
}

function hideReview()
{
	show('divShowReviews');
	hide('divHideReviews');
	hide('divReviews');
}

function showPhotos()
{
	hideReview();
	hide('divShowPhotos');
	show('divHidePhotos');
	show('divPhotos');
}

function hidePhotos()
{
	show('divShowPhotos');
	hide('divHidePhotos');
	hide('divPhotos');
}

function hide(id) {
    var x = getObj(id);
    x.style.display = "none";
}

function show(id) {
    var x = getObj(id);
    x.style.display = "block";
}
</script>


<style>
#tableReviews{
	  /*border: 1px solid #e7e6e8;*/
    border-collapse: collapse;
    width: 650px;
}
#tableReviews tr td{
	padding:4px;
	border: 2px solid #e7e6e8;
}
#divShowReviews, #divHideReviews, #divShowPhotos, #divHidePhotos{
	cursor:pointer;
}
#divShowReviews, #divShowPhotos{
	display:block;
}
#divHideReviews, #divReviews, #divHidePhotos, #divPhotos{
	display:none;
}

form {
       text-align: left;
       border: 2px solid #b3b3b3;
       width: 650px;
       padding-bottom: 10px;
       padding-left: 10px;
       font-weight: bold;
       line-height: 12px;
       background-color: #F3F3F3;
       display: inline-block;
}

.box{
	     border: 1px solid #ddd;
       width: 50%;
	     padding:10px;
       line-height: 12px;
       background-color: #fff;
       display: inline-block;
	     text-align:center;
}

#Search {
  margin-left: 45px;
}

#circle {
  margin-left: 284px;
}

.mytable{
	border-color:#fcfcfc;
	box-content:border-box;
}


.mytable td{
	padding-left:15px;
}

.mytable tr td:nth-child(2),.mytable tr td:nth-child(3){
	cursor:pointer;
}

</style>

</head>
<body>
<center>
  <form action="" method="post" name="myform" id="myform" onSubmit="before()">
  <h1 style="text-align:center; font-size:35px; font-weight:500; padding-bottom:0px;" ><i>Travel and Entertainment Search</i></h1>
  <hr style="height:1px; border:none; width:640px; background-color: #b3b3b3;">
  Keyword <input type="text" id="keyword" name="keyword" required style="margin-bottom:5px;"><br>
  Category <select name="category" style="margin-bottom:5px">
  <option value="default">default</option>
  <option value="cafe">cafe</option>
  <option value="bakery">bakery</option>
  <option value="restaurant">restaurant</option>
  <option value="beauty_salon">beauty salon</option>
  <option value="casino">casino</option>
  <option value="movie_theater">movie theater</option>
  <option value="lodging">lodging</option>
  <option value="airport">airport</option>
  <option value="train_station">train station</option>
  <option value="subway_station">subway station</option>
  <option value="bus_station">bus station</option>
  </select><br>
  Distance (miles)<input type="text" placeholder="10" name="distance" style="margin-bottom:5px; ">
  from <input onClick="document.getElementById('location').disabled=true;" type="radio" name="loc" id="lochere" value="here" checked style="margin-bottom:5px;"><span style="font-weight:normal;">Here</span><br>
  <input onClick="document.getElementById('location').disabled=false;" type="radio" name="loc" value="location" id="circle" ><input type="text" placeholder="location" name="location" id="location" required>
  <br><br>
  <input type="button" onClick="before()" value="Search" name="search" id="search" disabled>
  <input type="button" value="Clear" name="clear" id="clear" onClick="resetAll()">
  <br><br>
  <input type="hidden" name="action" value="form" />
  </form>

  <div id="main"></div>
  <div id="map" style="position:absolute; z-index:100; height:300px; width:350px;display:none; margin-top:0px; margin-left:15px;"></div>
  <div id="mapOptions" class="box" style="position:absolute; z-index:102; width:90px; padding:0px; display:none; margin-left:8px; margin-top:0px;">
  	<div onClick="routing('WALKING')" style="padding:4px;">Walk there</div>
    <div onClick="routing('BICYCLING')" style="padding:4px;">Bike there</div>
    <div onClick="routing('DRIVING')" style="padding:4px;">Drive there</div>
  </div>
</center>

<script type="application/javascript" src="http://ip-api.com/json/?callback=fetch"></script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCje8dLBNMjoTWeJkIMnfXz8-bd_QxoRpU&callback=initMap"></script>
<style>
#mapOptions div{
	cursor:pointer;
	background-color:#ccc;
	line-height:20px;
}
#mapOptions div:hover{
	background-color: #a6a6a6;
}
</style>
</body>
</html>

<?php

}
?>
