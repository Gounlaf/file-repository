/* global $, FileReader, Cropper */
function ImageUpload (aspectRatio, apiKey, redirectUrl) {
  var self = this

  this.aspectRatio = aspectRatio
  this.apiKey = apiKey
  this.cropper = null
  this.image = document.getElementById('image')
  this.redirectUrl = redirectUrl

  /**
   * Initializes and re-initializes the cropper
   */
  this.initializeCropper = function () {
    this.image.style.display = 'block'

    self.cropper = new Cropper(this.image, {
      aspectRatio: this.aspectRatio,
      responsive: true,
      guides: true
    })
  }

  /**
   * Gets the base64 encoded image URL
   *
   * @returns {string}
   */
  this.getImage = function () {
    return self.cropper.getCroppedCanvas().toDataURL()
  }

  /**
   * Change the mode between moving and cropping
   *
   * @param {string} mode
   */
  this.changeMode = function (mode) {
    if (self.cropper !== null) {
      self.cropper.setDragMode(mode)
    }
  }

  /**
   * Crop the image
   */
  this.accept = function (callback) {
    if (self.cropper !== null) {
      // crop
      self.image.src = self.getImage()

      // and upload
      self.upload(callback)
    }
  }

  /**
   * Get mime type name
   *
   * @returns {string}
   */
  this.getMime = function () {
    return self.getImage().split(';base64')[0].replace('data:', '')
  }

  /**
   * Get image extension basing on mime type name
   *
   * @returns {string}
   */
  this.getExtension = function () {
    switch (this.getMime()) {
      case 'image/jpeg':
      case 'image/jpg':
        return '.jpg'
      case 'image/png':
        return '.png'
      case 'image/gif':
        return '.gif'
      case 'image/webp':
        return '.webp'
    }

    return ''
  }

  /**
   * Upload the image to the server
   * and redirect ot the callback url specified in the constructor
   */
  this.upload = function (callback) {
    $.ajax({
      url: '/public/upload/image?_token=' + self.apiKey,
      method: 'POST',
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      data: JSON.stringify({
        content: self.getImage(),
        fileName: Math.random().toString(36).substr(2, 30) + self.getExtension(),
        mimeType: self.getMime()
      })
    }).done(function (data) {
      console.info(self.redirectUrl)
      console.debug(data)
      if (self.redirectUrl) {
        var replacement = b64EncodeUnicode(data.url)

        window.location.href = self.redirectUrl
          .replace('|url|', replacement)
          .replace('%257Curl%257C', replacement)
          .replace('%7Curl%7C', replacement)
      } else {
        self.destroy()
        self.initializeCropper()

        // Make sure the callback is a function
        if (typeof callback === 'function') {
          // Call it, since we have confirmed it is callable
          callback(data.url)
        }
      }
    })
  }

  /**
   * Deinitialize the cropper
   */
  this.destroy = function () {
    if (self.cropper !== null) {
      self.cropper.destroy()
    }
  }

  /**
   * Handles upload event
   *
   * @param e
   */
  this.handleImageUpload = function (e) {
    var reader = new FileReader()

    reader.onload = function (event) {
      // replace image and initialize the cropper again
      self.image.src = event.target.result
      self.destroy()
      self.initializeCropper()
    }
    reader.readAsDataURL(e.target.files[0])
  }
}

function b64EncodeUnicode (str) {
  return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function (match, p1) {
    return String.fromCharCode('0x' + p1)
  }))
}
