(function () {
    var script = document.currentScript;
    var searchId = script.getAttribute('data-search-id');
    var input = document.getElementById('watcherSearchInput');
    var results = document.getElementById('watcherSearchResults');
    var form = document.getElementById('watcherAddForm');
    var timer = null;

    if (!input) {
        return;
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var query = input.value.trim();
        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }
        timer = setTimeout(function () {
            fetch('/searches/watchers.php?ajax=search&id=' + encodeURIComponent(searchId) + '&q=' + encodeURIComponent(query))
                .then(function (response) { return response.json(); })
                .then(renderResults)
                .catch(function () { results.innerHTML = ''; });
        }, 300);
    });

    function renderResults(people) {
        results.innerHTML = '';
        people.forEach(function (person) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.textContent = person.displayName + ' (' + person.mail + ')';
            item.addEventListener('click', function () {
                document.getElementById('watcherEntraObjectId').value = person.id;
                document.getElementById('watcherDisplayName').value = person.displayName;
                document.getElementById('watcherEmail').value = person.mail;
                form.submit();
            });
            results.appendChild(item);
        });
    }
})();
