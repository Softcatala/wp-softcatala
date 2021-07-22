var URL='https://api.softcatala.org/summarization-service/v1'

function summarize_text() {

    text = document.getElementById('source_text').value;
    num_sentences = document.getElementById('num_sentences').value;
    url = URL + `/summarize_by_sentence`;

    var post_data = new FormData();
    post_data.append("text", text);
    post_data.append("num_sentences", num_sentences);

    jQuery.ajax({
        url: url,
        type: 'POST',
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success : print_results,
        error : ko_function
    });
}

function print_results(result) {

    text = result['summary']
    jQuery('#summarized_text').val(text)

    text = result['time']
    jQuery('#time_used').text(text)
}

function ko_function(result) {
    text = "S'ha produït un error i no s'ha pogut completar la petició"
    jQuery('#summarized_text').val(text)
}
