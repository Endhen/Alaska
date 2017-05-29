// Empty all text fields
var textareaElt = document.getElementsByTagName('textarea'),
    i = 0;

for (i; i < textareaElt.length; i++) {
    textareaElt[i].textContent = '';
}

//add a background out of the class container
document.body.insertAdjacentHTML('afterBegin', '<div class="img"></div>');