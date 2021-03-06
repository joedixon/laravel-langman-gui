<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Langman</title>

    <!-- Style sheets-->
    <link href='{{asset('vendor/langman/langman.css')}}' rel='stylesheet' type='text/css'>

    <!-- Icons -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
</head>
<body>
<div id="app" v-cloak>

    <nav class="navbar navbar-toggleable-md navbar-light mb-4">
        <div class="container">

            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <span class="navbar-text">@{{ _.toArray(currentLanguageTranslations).length }} Keys</span>
                </li>
                <li class="nav-item active ml-3" v-if="_.toArray(currentLanguageUntranslatedKeys).length">
                    <span class="navbar-text text-danger">@{{ _.toArray(currentLanguageUntranslatedKeys).length }} Un-translated</span>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto mr-3">
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @{{ selectedLanguage }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a v-for="lang in languages"
                           href="#" role="button"
                           v-on:click="selectedLanguage = lang"
                           class="dropdown-item" href="#">@{{ lang }}</a>
                    </div>
                </li>
                <li class="nav-item" style="position:relative;">
                    <div class="input-group categorySearch">
                        <div class="input-group-addon"><i class="fa fa-search"></i></div>
                        <input type="text" class="form-control" v-model="searchCategory" placeholder="Search category">
                    </div>
                    <div class="list-group" v-if="this.searchCategory" style="position: absolute; width: 100%; z-index:2;">
                        <a href="#" role="button"
                            v-for="file in filteredFiles"
                            v-on:click="selectFile(file)"
                            :class="['list-group-item', 'list-group-item-action']">
                            <div class="d-flex w-100 justify-content-between" style="overflow: hidden;">
                                <strong v-html="stripExtension(file)"></strong>
                            </div>
                        </a>
                    </div>
                </li>
            </ul>
        
            <button class="btn btn-outline-info btn-sm mr-2"
                    v-on:click="promptToAddNewKey" v-if="languages.length"
                    type="button">Add
            </button>
            <button class="btn btn-outline-info btn-sm mr-2"
                    v-on:click="scanForKeys" v-if="languages.length"
                    type="button">Scan
            </button>
            <button class="btn btn-outline-success btn-sm"
                    v-on:click="save" v-if="languages.length"
                    type="button">Save
                <small v-if="this.hasChanges" class="text-danger">&#9679;</small>
            </button>
        </div>

    </nav>

    <div class="container">
        <div class="row" v-if="baseLanguage && _.toArray(currentLanguageTranslations).length">
            <div class="col">
                <div class="input-group mainSearch">
                    <div class="input-group-addon"><i class="fa fa-search"></i></div>
                    <input 
                        type="text" 
                        class="form-control" 
                        v-model="searchPhrase" 
                        placeholder="Search"
                    >
                </div>

                <div class="mt-4" style="overflow: scroll; height: 500px">
                    <div class="list-group">

                        <a href="#" role="button"
                           v-for="line in filteredTranslations"
                           v-on:click="selectedKey = line.key; selectedFile = line.file"
                           :class="['list-group-item', 'list-group-item-action', {'list-group-item-danger': !line.value}]">
                            <div class="d-flex w-100 justify-content-between">
                                <strong class="mb-1" v-html="highlight(line.key)"></strong>
                            </div>
                            <small class="text-muted" v-html="highlight(line.value)"></small>
                        </a>

                    </div>
                </div>
            </div>
            <div class="col">
                <div v-if="selectedFile && selectedKey">

                    <p class="mb-4">
                        @{{ selectedKey }}
                        <button class="btn btn-outline-warning btn-sm mr-2"
                            style="float: right; margin-right: 0 !important;"
                            type="button"
                            v-on:click="toggleTextDirection">@{{ textDirection == 'ltr' ? 'RTL' : 'LTR'}}
                        </button>
                    </p>

                <textarea name="" rows="10" class="form-control mb-4"
                          v-model="translations[selectedLanguage][selectedFile][selectedKey]"
                          v-bind:dir="textDirection"
                          placeholder="Translate..."></textarea>

                    <textarea name="" rows="10" class="form-control mb-4" 
                          v-if="typeof selected === 'object'"
                          v-for="(line, index) in selected"
                          v-model="translations[selectedLanguage][selectedFile][selectedKey][index]"
                          v-bind:dir="textDirection"
                          placeholder="Translate...">@{{ line }}</textarea>

                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-danger btn-sm" v-on:click="removeKey(selectedKey)">Delete this key</button>
                    </div>

                </div>

                <h5 class="text-muted text-center" v-else>
                    .<br>
                    .<br>
                    .<br><br>
                    Select a key from the list to the left
                </h5>
            </div>
        </div>

        <div v-else>
            <p class="lead text-center" v-if="!languages.length">
                There are no JSON language files in your project.<br>
                <button class="btn btn-outline-primary mt-3" v-on:click="addLanguage">Add Language</button>
            </p>

            <p class="lead text-center" v-if="languages.length">
                There are no Translation lines yet, start by adding new keys or <br>
                <a href="#" role="button" v-on:click="scanForKeys">scan</a> your project for lines to translate.
            </p>
        </div>
    </div>
</div>

<script>
    const langman = {
        csrf: "{{csrf_token()}}",
        baseLanguage: '{!! config('langmanGUI.base_language') !!}',
        languages: {!! json_encode($languages) !!},
        translations: {!! json_encode($translations) !!}
    };
</script>
<script src="{{asset('vendor/langman/langman.js')}}"></script>
</body>
</html>