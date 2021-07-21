var URL='https://api.softcatala.org/summarization-service/v1/'

function summarize_text() {

    text = document.getElementById('source_text').value;
    num_sentences = document.getElementById('num_sentences').value;
    
    var xhr = new XMLHttpRequest();
    url = URL + `/summarize_by_sentence`;
    xhr.open('POST', url);

    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
//            alert(xhr.responseText);
            json = JSON.parse(xhr.responseText);

            element = document.getElementById('translated_text');
            text = json["summary"];
            element.value = text;

            element = document.getElementById('time_used');
            text = json["time"];
            element.innerText = text;

        }
    }

    var payload = new FormData();
    payload.append("text", text);
    payload.append("num_sentences", num_sentences);
    xhr.send(payload);
}


