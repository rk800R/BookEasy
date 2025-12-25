/* js/users.js
    Simple client-side user + booking management using localStorage
*/
/* Muhammad Anas */
/* Helper functions */
function readLS(key){ try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){ return null; } }
function writeLS(key,val){ localStorage.setItem(key, JSON.stringify(val)); }
function uid(prefix='id'){ return prefix + Math.random().toString(36).slice(2,9); }

/* Keys */
const LS_USERS = 'users';
const LS_BOOKINGS = 'bookings';
const LS_CURRENT = 'current_user';

/* Initialize stores if missing */
if (!readLS(LS_USERS)) writeLS(LS_USERS, []);
if (!readLS(LS_BOOKINGS)) writeLS(LS_BOOKINGS, []);

/* Registration */
function registerUser(name, email, password){
   const users = readLS(LS_USERS) || [];
   if (!name || !email || !password) return {ok:false, msg:'Fill all fields'};
   if (users.find(u => u.email.toLowerCase() === email.toLowerCase())) return {ok:false, msg:'Email already registered'};
   const user = { id: uid('u_'), name, email: email.toLowerCase(), password: password };
   users.push(user);
   writeLS(LS_USERS, users);
   return {ok:true, msg:'Registered', user: { id: user.id, name: user.name, email: user.email }};
}

/* Login */
function loginUser(email, password){
   const users = readLS(LS_USERS) || [];
   if (!email || !password) return { ok:false, msg:'Fill all fields' };
   const u = users.find(x => x.email === (email||'').toLowerCase() && x.password === password);
   if (!u) return { ok:false, msg:'Invalid credentials' };
   const safe = { id: u.id, name: u.name, email: u.email };
   writeLS(LS_CURRENT, safe);
   return { ok:true, user: safe };
}

/* Current user */
function currentUser(){
   return readLS(LS_CURRENT) || null;
}

function logout(){
   sessionStorage.removeItem('currentUser');
   localStorage.removeItem(LS_CURRENT);
}

/* Bookings */
function addBooking(userId, roomId, roomTitle, from, to, extras, price){
   const rows = readLS(LS_BOOKINGS) || [];
   const b = {
      id: uid('b_'),
      userId,
      roomId,
      roomTitle,
      from,
      to,
      extras: extras || [],
      price: price || 0,
      completed: false,
      createdAt: new Date().toISOString()
   };
   rows.push(b);
   writeLS(LS_BOOKINGS, rows);
   return b;
}

function getCurrentBookings(userId){
   const rows = readLS(LS_BOOKINGS) || [];
   return rows.filter(r => r.userId === userId && !r.completed).sort((a,b)=> (a.from||'').localeCompare(b.from||''));
}

function getOldBookings(userId){
   const rows = readLS(LS_BOOKINGS) || [];
   return rows.filter(r => r.userId === userId && r.completed).sort((a,b)=> (b.createdAt||'').localeCompare(a.createdAt||''));
}

function completeBooking(id){
   const rows = readLS(LS_BOOKINGS) || [];
   const idx = rows.findIndex(r=>r.id===id);
   if (idx === -1) return { ok:false, msg:'Booking not found' };
   rows[idx].completed = true;
   writeLS(LS_BOOKINGS, rows);
   return { ok:true };
}

function rateBooking(id, rating){
   const rows = readLS(LS_BOOKINGS) || [];
   const idx = rows.findIndex(r=>r.id===id);
   if (idx === -1) return { ok:false, msg:'Booking not found' };
   const n = parseInt(rating,10);
   if (!n || n < 1 || n > 5) return { ok:false, msg:'Invalid rating' };
   rows[idx].rating = n;
   writeLS(LS_BOOKINGS, rows);
   return { ok:true };
}

/* Expose API */
window.registerUser = registerUser;
window.loginUser = loginUser;
window.currentUser = currentUser;
window.logout = logout;
window.addBooking = addBooking;
window.getCurrentBookings = getCurrentBookings;
window.getOldBookings = getOldBookings;
window.completeBooking = completeBooking;
window.rateBooking = rateBooking;
