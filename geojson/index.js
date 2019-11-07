var digits=2;
var fs = require("fs");
var d = JSON.parse(fs.readFileSync("province_.geojson"));
d.features.forEach(vvv => vvv.geometry.coordinates.forEach(vv => vv[0].forEach(v => {
  v[0]=v[0].toFixed(digits)*1;
  v[1]=v[1].toFixed(digits)*1;
})));
fs.writeFileSync('./../public/province.geojson',JSON.stringify(d));
fs.writeFileSync('./province.geojson',JSON.stringify(d));
var d = JSON.parse(fs.readFileSync("district_.geojson"));
d.features.forEach(vvv => vvv.geometry.coordinates.forEach(vv => vv[0].forEach(v => {
  v[0]=v[0].toFixed(digits)*1;
  v[1]=v[1].toFixed(digits)*1;
})));
fs.writeFileSync('./../public/district.geojson',JSON.stringify(d));
fs.writeFileSync('./district.geojson',JSON.stringify(d));
