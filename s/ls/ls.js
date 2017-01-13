'use strict';

/*
  context quick search
*/

// model

let listUl = document.querySelector('ul');
let dir = document.title.match(/^Ls (.+)$/)[1];
let ls = Array.prototype.slice.call(listUl.children)
  .map(u => u.querySelector('a'))
  .map(u => ({
    href: u.getAttribute('href'),
    text: u.textContent
  }));
let viewLs = ls;

function updateDir(newDir) {
  dir = newDir
  document.title = 'Ls ' + dir;
}

function insertionDistance(s, t, sI) {
  s = s.toLowerCase();
  t = t.toLowerCase();

  let sA = Array.prototype.slice.call(s);
  sI = sI || [];

  sA.reduce((v, u) => {
    let st = t.indexOf(u, v + 1);
    sI.push(st);
    return st;
  }, -1);

  let d = 0;

  if (sI[0] === -1)
    return NaN;
  else if(sI[sI.length - 1] === -1)
    return NaN;

  sI.reduce((v, u, i) => {
    if (u <= v)
      return NaN;
    d += u - 1 - v;
    return u;
  });

  d += sI[0] * 100;
  d += (t.length - 1 - sI[sI.length - 1]) / 100;

  return d;
}

function fuzzySearch(haystack, needle) {
  let result = haystack.map(u => {
    let sI = [];
    return [u, insertionDistance(needle, u.text, sI), sI];
  }).filter(u => !isNaN(u[1])).sort((u, v) => u[1] - v[1]);

  return result;
}

function filter(q) {
  if (q === '')
    return [ls];
  let r = fuzzySearch(ls, q);
  return [r.map(u => u[0]), r.map(u => u[2])];
}

// presenter

function buildUl(ls, toBold) {
  let ul = document.createElement('ul');
  ls.forEach((u, i) => {
    let li = document.createElement('li');
    let a = document.createElement('a');
    a.setAttribute('href', u.href);
    if (toBold) {
      let t = u.text;
      let v = toBold[i];

      v.reduce((v, u) => {
        if (v + 1 < u)
          a.appendChild(document.createTextNode(t.substring(v + 1, u)));
        let b = document.createElement('b');
        b.textContent = t.substr(u, 1);
        a.appendChild(b);
        return u;
      }, -1);
      a.appendChild(document.createTextNode(t.substr(v[v.length - 1] + 1)));
    } else {
      a.textContent = u.text;
    }
    li.appendChild(a);
    ul.appendChild(li);
  });
  return ul;
}

function updateLs(newLs, toBold) {
  viewLs = newLs;
  let newUl = buildUl(viewLs, toBold);

  listUl.parentNode.replaceChild(newUl, listUl);
  listUl = newUl;
}

// view

let queryInp = document.querySelector('input');
queryInp.addEventListener('input', e => {
  let r = filter(queryInp.value);
  updateLs(r[0], r[1]);
});

/*
  subdirectory viewer
*/

function xhr(url) {
  return new Promise(done => {
    let req = new XMLHttpRequest();
    req.open('GET', url);
    req.send();
    req.onreadystatechange = () =>
      req.readyState === 4 && req.status === 200 && done(req.responseText);
  });
}

function goDir(cd) {
  xhr('/ls.php?d=' + cd).then(u => {
    updateDir(cd);
    updateLs(ls = JSON.parse(u));
  });
}

let searchForm = document.querySelector('form');
searchForm.addEventListener('submit', e => {
  e.preventDefault();

  if (!viewLs.length)
    return;

  let href = viewLs[0].href;
  if (href.substring(href.length - 1) === '/') {
    goDir(href);
    queryInp.value = '';
  } else {
    location.href = href;
  }
});
