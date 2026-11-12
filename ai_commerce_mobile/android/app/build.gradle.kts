import java.io.FileInputStream
import java.util.Properties

plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

val keystoreProperties = Properties()
val keystorePropertiesFile = rootProject.file("key.properties")
if (keystorePropertiesFile.exists()) {
    FileInputStream(keystorePropertiesFile).use { keystoreProperties.load(it) }
}

fun signingValue(propertyKey: String, envKey: String, gradleKey: String): String? {
    return keystoreProperties.getProperty(propertyKey)?.takeIf { it.isNotBlank() }
        ?: providers.environmentVariable(envKey).orNull?.takeIf { it.isNotBlank() }
        ?: providers.gradleProperty(gradleKey).orNull?.takeIf { it.isNotBlank() }
}

android {
    namespace = "com.aicommerce.ai_commerce_mobile"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.aicommerce.ai_commerce_mobile"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        create("release") {
            val releaseStoreFile = signingValue(
                "storeFile",
                "ANDROID_KEYSTORE_PATH",
                "android.injected.signing.store.file"
            )
            if (releaseStoreFile != null) {
                storeFile = file(releaseStoreFile)
            }
            storePassword = signingValue(
                "storePassword",
                "ANDROID_KEYSTORE_PASSWORD",
                "android.injected.signing.store.password"
            )
            keyAlias = signingValue(
                "keyAlias",
                "ANDROID_KEY_ALIAS",
                "android.injected.signing.key.alias"
            )
            keyPassword = signingValue(
                "keyPassword",
                "ANDROID_KEY_PASSWORD",
                "android.injected.signing.key.password"
            )
        }
    }

    buildTypes {
        debug {
            signingConfig = signingConfigs.getByName("debug")
        }
        release {
            signingConfig = signingConfigs.getByName("release")
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}

tasks.matching {
    it.name in setOf("packageRelease", "packageReleaseUniversalApk", "signReleaseBundle")
}.configureEach {
    doFirst {
        val releaseSigning = android.signingConfigs.getByName("release")
        val missing = buildList {
            if (releaseSigning.storeFile == null) add("storeFile")
            if (releaseSigning.storePassword.isNullOrBlank()) add("storePassword")
            if (releaseSigning.keyAlias.isNullOrBlank()) add("keyAlias")
            if (releaseSigning.keyPassword.isNullOrBlank()) add("keyPassword")
        }
        if (missing.isNotEmpty()) {
            throw GradleException(
                "Release signing is not configured (missing: ${missing.joinToString(", ")}). " +
                    "Provide android/key.properties or ANDROID_KEYSTORE_* environment variables."
            )
        }
        val keystoreFile = releaseSigning.storeFile!!
        if (!keystoreFile.exists()) {
            throw GradleException(
                "Release keystore file not found at configured path: ${keystoreFile.path}"
            )
        }
    }
}
