const backend = process.env.BACKEND_URL || "http://localhost:8080/api";

async function authFetch(url, options = {}) {
  const r = await fetch(backend + url, {
    credentials: "include",
    ...options
  });
  if (r.status == 401) {
    const e = new Error("unauthorized");
    e.unauthorized = true;
    throw e;
  }
  if (Math.floor(r.status / 100) != 2) {
    const e = new Error(await r.text());
    throw e;
  }
  return r;
}

async function getJSON(url) {
  const r = await authFetch(url);
  return r.json();
}

function postData(val) {
  if (Array.isArray(val)) {
    return val.map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join("&");
  }
  if (typeof val == "object") {
    return postData(Object.entries(val));
  }
  throw new Error("unknown POST payload type: " + typeof val);
}

async function post(url, data) {
  const r = await authFetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: postData(data)
  });
  return r;
}

function postJSON(url, data) {
  return authFetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  });
}

export default {
  async login(login, password) {
    const response = await post("/login", { login, password });
    const body = await response.text();
    if (response.status != 201) {
      throw new Error("login failed: " + body);
    }
  },

  logout() {
    return post("/logout", {}).then(r => r.text());
  },

  dicts() {
    return getJSON("/");
  },

  async dict(id) {
    const dicts = await this.dicts();
    return dicts.find(d => d.id == id);
  },

  updateDict(id, body) {
    return postJSON(`/${id}`, body);
  },

  test(dictID) {
    return getJSON(`/${dictID}/test`);
  },

  submitAnswers(dictID, entries) {
    // console.log(entries);
    // [
    //   ["q[]", "69"],
    //   ["dir[]", "0"],
    //   ["a[]", "hand"],
    //   ["q[]", "496"],
    //   ["dir[]", "0"],
    //   ["a[]", ""]
    // ];
    return post(`/${dictID}/test`, entries).then(r => r.json());
  },

  entry(id) {
    return getJSON(`/entries/${id}`);
  },

  updateEntry(id, entry) {
    return post(`/entries/${id}`, { q: entry.q, a: entry.a });
  },

  addEntries(dictID, entries) {
    const string = entries.map(e => `${e.q} - ${e.a}`).join("\n");
    return post(`/${dictID}/add`, { words: string }).then(r => r.json());
  },

  dump: () => getJSON("/export"),
  load: data => postJSON("/export", data)
};
