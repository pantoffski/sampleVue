import Vue from 'vue';
import Vuex from 'vuex';
import axios from "axios";

axios.defaults.baseURL = process.env.VUE_APP_BASE_URL;
axios.interceptors.request.use(
  req => {
    if (req.method === "delete") {
      req.method = "post";
      req.headers["X-HTTP-Method-Override"] = "DELETE";
    }
    return req;
  },
  error => Promise.reject(error)
);
Vue.prototype.$http = axios;
Vue.use(Vuex);

const JWT_COOKIE_NAME = "kstjToken";
var cookie = require("js-cookie");
var Base64 = require("base-64");

export default new Vuex.Store({
 modules: {
  auth:{
    namespaced: true,
    state: {
      token: null
    },
    mutations: {
      setToken(state, token) {
        if (token != "") {
          state.token = JSON.parse(Base64.decode(token.split(".")[1]));
          axios.defaults.headers.common["Authorization"] = `BEARER ${token}`;
          var expires = Math.floor(state.token.exp - new Date().getTime() / 1000) / 60 / 60 / 24;
          cookie.set(JWT_COOKIE_NAME, token, { expires: expires });
        } else {
          state.token = null;
          axios.defaults.headers.common["Authorization"] = "";
          cookie.remove(JWT_COOKIE_NAME);
        }
      },
      logout(state) {
        state.token = null;
        axios.defaults.headers.common["Authorization"] = "";
        cookie.remove(JWT_COOKIE_NAME);
      },
    },
    actions: {
      init({ dispatch,commit }) {
        return new Promise(resolve => {
          var token = cookie.get(JWT_COOKIE_NAME);
          if (token) {
            try {
              commit("setToken", token);
              dispatch("refreshToken");
              resolve(true);
            } catch (e) {
              resolve(false);
            }
          } else {
            resolve(false);
          }
        });
      },
      refreshToken({commit}){
        axios.get("/api/auth/refresh").then(r=>{
          commit("setToken", r.data.token);
        }).catch(e=>{
          commit("logout");
        })

      },
      login({ commit }, postObj) {
        return new Promise(resolve => {
          axios
            .post("/api/auth/login", postObj)
            .then(r => {
              try {
                commit("setToken", r.data.token);
                resolve(true);
              } catch (e) {
                resolve(false);
              }
            })
            .catch(r => {
              if(r.response&&r.response.data)
              return resolve(r.response.data);
              resolve(false);
            });
        });
      }
    }
  }
}
});

