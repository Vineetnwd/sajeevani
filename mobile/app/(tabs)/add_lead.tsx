import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, TextInput, TouchableOpacity, ScrollView, Alert, ActivityIndicator, Image } from 'react-native';
import * as Location from 'expo-location';
import * as ImagePicker from 'expo-image-picker';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function LeadSubmissionScreen() {
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    age: '',
    gender: 'Male',
    address: '',
    bp: '',
    sugar: '',
    weight: '',
    pulse: '',
    symptoms: ''
  });

  const [location, setLocation] = useState<Location.LocationObject | null>(null);
  const [photo, setPhoto] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [locationLoading, setLocationLoading] = useState(false);

  // Update this URL to your Hostinger Live URL or Local Machine IP when testing locally!
  // Example Live: 'https://yourdomain.com/web/api/leads.php?action=submit_lead'
  // Example Local: 'http://192.168.1.13:8000/api/leads.php?action=submit_lead'
  const API_URL = 'https://praanveda.net/web/api/leads.php?action=submit_lead';

  const handleChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const submitLead = async () => {
    if (!formData.name || !formData.phone || !formData.age) {
      Alert.alert('Validation Error', 'Name, Phone, and Age are required!');
      return;
    }

    setIsSubmitting(true);

    try {
      // Get real user ID
      const userId = await AsyncStorage.getItem('userId') || '1';

      // Convert to FormData for PHP processing
      const data = new FormData();
      data.append('executive_id', userId);

      Object.keys(formData).forEach(key => {
        data.append(key, formData[key as keyof typeof formData]);
      });

      if (location) {
        data.append('latitude', location.coords.latitude.toString());
        data.append('longitude', location.coords.longitude.toString());
      }

      if (photo) {
        const filename = photo.split('/').pop() || 'photo.jpg';
        const match = /\.(\w+)$/.exec(filename);
        const type = match ? `image/${match[1]}` : `image`;

        data.append('photo', {
          uri: photo,
          name: filename,
          type,
        } as any);
      }

      const response = await fetch(API_URL, {
        method: 'POST',
        headers: {
          // No 'Content-Type' header needed for FormData; fetch sets it automatically with boundary.
          'Accept': 'application/json',
        },
        body: data
      });

      const result = await response.json();

      if (result.status === 'success') {
        Alert.alert('Success!', 'Lead has been successfully submitted to the Doctor Queue.');
        // Clear form
        setFormData({
          name: '', phone: '', age: '', gender: 'Male', address: '',
          bp: '', sugar: '', weight: '', pulse: '', symptoms: ''
        });
        setPhoto(null);
        setLocation(null);
      } else {
        Alert.alert('Error', result.message || 'Something went wrong');
      }
    } catch (error) {
      Alert.alert('Network Error', 'Failed to connect to the server. Check your API_URL and ensure the web panel is running.');
      console.error(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>New Patient Lead</Text>
          <Text style={styles.headerSubtitle}>Field Executive Entry</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Basic Information</Text>

          <TextInput style={styles.input} placeholder="Patient Full Name"
            value={formData.name} onChangeText={(text) => handleChange('name', text)} />

          <View style={styles.row}>
            <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="Phone Number"
              keyboardType="phone-pad" value={formData.phone} onChangeText={(text) => handleChange('phone', text)} />
            <TextInput style={[styles.input, { width: 80 }]} placeholder="Age"
              keyboardType="numeric" value={formData.age} onChangeText={(text) => handleChange('age', text)} />
          </View>

          <TextInput style={styles.input} placeholder="Complete Address"
            value={formData.address} onChangeText={(text) => handleChange('address', text)} />
        </View>

        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Vitals & Symptoms</Text>

          <View style={styles.row}>
            <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="BP (e.g. 120/80)"
              value={formData.bp} onChangeText={(text) => handleChange('bp', text)} />
            <TextInput style={[styles.input, { flex: 1 }]} placeholder="Sugar"
              value={formData.sugar} onChangeText={(text) => handleChange('sugar', text)} />
          </View>

          <View style={styles.row}>
            <TextInput style={[styles.input, { flex: 1, marginRight: 10 }]} placeholder="Weight (kg)"
              keyboardType="numeric" value={formData.weight} onChangeText={(text) => handleChange('weight', text)} />
            <TextInput style={[styles.input, { flex: 1 }]} placeholder="Pulse (bpm)"
              keyboardType="numeric" value={formData.pulse} onChangeText={(text) => handleChange('pulse', text)} />
          </View>

          <TextInput style={[styles.input, { height: 80, textAlignVertical: 'top' }]}
            placeholder="Detailed Symptoms / Patient Notes" multiline={true}
            value={formData.symptoms} onChangeText={(text) => handleChange('symptoms', text)} />
        </View>

        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Attachments & Location</Text>

          <TouchableOpacity style={styles.actionBtn} onPress={async () => {
            setLocationLoading(true);
            try {
              let { status } = await Location.requestForegroundPermissionsAsync();
              if (status !== 'granted') {
                Alert.alert('Permission Denied', 'Permission to access location was denied');
                setLocationLoading(false);
                return;
              }

              // Try getting the last known position first (fastest, solves emulator hangs)
              let loc = await Location.getLastKnownPositionAsync({});

              if (!loc) {
                // If no last known position, try getting the current one
                loc = await Location.getCurrentPositionAsync({
                  accuracy: Location.Accuracy.Balanced
                });
              }

              if (loc) {
                setLocation(loc);
              } else {
                Alert.alert('Location Error', 'Unable to fetch your GPS coordinates. Ensure your device location is turned on.');
              }
            } catch (error) {
              Alert.alert('Location Error', 'Something went wrong while fetching location.');
              console.error(error);
            } finally {
              setLocationLoading(false);
            }
          }}>
            {locationLoading ? <ActivityIndicator color="#059669" /> : <Text style={styles.actionBtnText}>{location ? `Location Captured ✓` : 'Get GPS Location'}</Text>}
          </TouchableOpacity>

          <TouchableOpacity style={styles.actionBtn} onPress={async () => {
            let result = await ImagePicker.launchCameraAsync({
              mediaTypes: ImagePicker.MediaTypeOptions.Images,
              allowsEditing: true,
              quality: 0.8,
            });

            if (!result.canceled) {
              setPhoto(result.assets[0].uri);
            }
          }}>
            <Text style={styles.actionBtnText}>Capture Patient Photo</Text>
          </TouchableOpacity>

          {photo && (
            <Image source={{ uri: photo }} style={styles.previewImage} />
          )}
        </View>

        <TouchableOpacity style={styles.submitBtn} onPress={submitLead} disabled={isSubmitting}>
          {isSubmitting ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.submitBtnText}>Submit Lead to Doctor Panel</Text>
          )}
        </TouchableOpacity>

      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  header: {
    marginBottom: 24,
    marginTop: 10,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#111827',
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#059669', // Teal/Green accent
    fontWeight: '600',
    marginTop: 4,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#374151',
    marginBottom: 16,
  },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginBottom: 12,
    fontSize: 15,
    color: '#1F2937',
  },
  row: {
    flexDirection: 'row',
  },
  submitBtn: {
    backgroundColor: '#059669',
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#059669',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  submitBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  actionBtn: {
    backgroundColor: '#E5E7EB',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  actionBtnText: {
    color: '#374151',
    fontWeight: '600',
  },
  previewImage: {
    width: '100%',
    height: 200,
    borderRadius: 8,
    marginTop: 8,
  }
});
