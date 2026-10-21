function myFunction() {
 var x = document.getElementById("nav");
  if (x.className.indexOf("w3-show") == -1) {
  x.className += " w3-show";
} else { 
x.className = x.className.replace(" w3-show", "");
}
}

function generateQRCode(url, size) {
    const qrCodeContainer = document.getElementById("qrcode");
    new QRCode(qrCodeContainer, {
        text: url,
        width: size,
        height: size,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}
