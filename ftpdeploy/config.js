module.exports = {
  localBasePath: "/",
  remoteBasePath: "/",
  sync: [
    { src: "/dist", dest: "/" },
    { src: "/cfg", dest: "/cfg"},
    { src: { dir: "/api", ignore: "api/.htaccess" }, dest: "/api" }
  ]
};
