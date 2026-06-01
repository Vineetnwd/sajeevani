import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet, View, Text, ScrollView, TouchableOpacity,
  TextInput, ActivityIndicator, Alert, StatusBar, FlatList
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';

const API_BASE = 'https://praanveda.net/web/api/mr.php';

type Doctor = { id: number; name: string; phone: string };
type Product = { id: number; name: string; description: string; price: string; stock_quantity: number };
type CartItem = { product: Product; quantity: number };

export default function NewOrderScreen() {
  const [step, setStep] = useState<1 | 2>(1);
  const [mrId, setMrId] = useState('');
  const [notes, setNotes] = useState('');

  // Step 1: Doctors
  const [doctors, setDoctors] = useState<Doctor[]>([]);
  const [doctorsLoading, setDoctorsLoading] = useState(true);
  const [doctorSearch, setDoctorSearch] = useState('');
  const [selectedDoctor, setSelectedDoctor] = useState<Doctor | null>(null);

  // Add Doctor Modal
  const [addDoctorVisible, setAddDoctorVisible] = useState(false);
  const [newDoctorName, setNewDoctorName] = useState('');
  const [newDoctorPhone, setNewDoctorPhone] = useState('');
  const [isAddingDoctor, setIsAddingDoctor] = useState(false);

  // Step 2: Products + Cart
  const [products, setProducts] = useState<Product[]>([]);
  const [productsLoading, setProductsLoading] = useState(false);
  const [productSearch, setProductSearch] = useState('');
  const [cart, setCart] = useState<Map<number, CartItem>>(new Map());

  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    AsyncStorage.getItem('userId').then(id => setMrId(id || ''));
    fetchDoctors();
  }, []);

  const fetchDoctors = async () => {
    try {
      const res = await fetch(`${API_BASE}?action=get_doctors&t=${Date.now()}`);
      const result = await res.json();
      if (result.status === 'success') {
        setDoctors(result.data || []);
      }
    } catch (e) {
      Alert.alert('Error', 'Failed to load doctors. Check your connection.');
    } finally {
      setDoctorsLoading(false);
    }
  };

  const fetchProducts = async () => {
    setProductsLoading(true);
    try {
      const res = await fetch(`${API_BASE}?action=get_products&t=${Date.now()}`);
      const result = await res.json();
      if (result.status === 'success') {
        setProducts(result.data || []);
      }
    } catch (e) {
      Alert.alert('Error', 'Failed to load products.');
    } finally {
      setProductsLoading(false);
    }
  };

  const handleSelectDoctor = (doctor: Doctor) => {
    setSelectedDoctor(doctor);
    setStep(2);
    fetchProducts();
  };

  const handleAddDoctor = async () => {
    if (!newDoctorName.trim() || !newDoctorPhone.trim()) {
      Alert.alert('Error', 'Please enter name and phone number');
      return;
    }
    setIsAddingDoctor(true);
    try {
      const formData = new FormData();
      formData.append('name', newDoctorName);
      formData.append('phone', newDoctorPhone);

      const res = await fetch(`${API_BASE}?action=add_doctor`, {
        method: 'POST',
        body: formData,
      });
      const result = await res.json();
      if (result.status === 'success') {
        Alert.alert('Success', 'Doctor added successfully');
        setAddDoctorVisible(false);
        setNewDoctorName('');
        setNewDoctorPhone('');
        fetchDoctors(); // Refresh list
      } else {
        Alert.alert('Error', result.message || 'Failed to add doctor');
      }
    } catch (e) {
      Alert.alert('Error', 'Network error. Try again.');
    } finally {
      setIsAddingDoctor(false);
    }
  };

  const updateCart = (product: Product, delta: number) => {
    setCart(prev => {
      const newCart = new Map(prev);
      const current = newCart.get(product.id);
      const newQty = (current?.quantity || 0) + delta;

      if (newQty > product.stock_quantity) {
        Alert.alert('Stock Limit', `Only ${product.stock_quantity} units of ${product.name} are available in stock.`);
        return prev;
      }

      if (newQty <= 0) {
        newCart.delete(product.id);
      } else {
        newCart.set(product.id, { product, quantity: newQty });
      }
      return newCart;
    });
  };

  const cartTotal = Array.from(cart.values()).reduce(
    (sum, item) => sum + parseFloat(item.product.price) * item.quantity, 0
  );
  const cartCount = Array.from(cart.values()).reduce((sum, item) => sum + item.quantity, 0);

  const filteredDoctors = doctors.filter(d => {
    const dName = d.name || '';
    const dPhone = d.phone || '';
    return dName.toLowerCase().includes(doctorSearch.toLowerCase()) || dPhone.includes(doctorSearch);
  });

  const filteredProducts = products.filter(p =>
    p.name.toLowerCase().includes(productSearch.toLowerCase())
  );

  const handleSubmit = async () => {
    if (cart.size === 0) {
      Alert.alert('Empty Cart', 'Please add at least one product to the order.');
      return;
    }

    setSubmitting(true);
    try {
      const items = Array.from(cart.values()).map(item => ({
        product_id: item.product.id,
        quantity: item.quantity,
        unit_price: item.product.price,
      }));

      const formData = new FormData();
      formData.append('mr_id', mrId);
      formData.append('doctor_id', selectedDoctor!.id.toString());
      formData.append('notes', notes);
      formData.append('items', JSON.stringify(items));

      const res = await fetch(`${API_BASE}?action=place_order`, {
        method: 'POST',
        body: formData,
      });
      const result = await res.json();

      if (result.status === 'success') {
        Alert.alert(
          '✅ Order Placed!',
          `Order #DO-${result.data.order_id} submitted successfully.\nTotal: ₹${parseFloat(result.data.total_amount).toFixed(2)}`,
          [{
            text: 'View My Orders',
            onPress: () => { resetForm(); router.push('/(mr)/my_orders'); }
          }, {
            text: 'New Order',
            onPress: () => resetForm()
          }]
        );
      } else {
        Alert.alert('Error', result.message || 'Failed to place order.');
      }
    } catch (e) {
      Alert.alert('Network Error', 'Could not connect. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const resetForm = () => {
    setStep(1);
    setSelectedDoctor(null);
    setCart(new Map());
    setNotes('');
    setDoctorSearch('');
    setProductSearch('');
  };

  // ───── STEP 1: Doctor Selection ─────
  if (step === 1) {
    return (
      <View style={{ flex: 1, backgroundColor: '#064E3B' }}>
        <SafeAreaView style={{ flex: 1, backgroundColor: '#F0FDF4' }} edges={['top']}>
          <StatusBar barStyle="light-content" backgroundColor="#064E3B" />

          <View style={styles.stepHeaderWrapper}>
            <View style={styles.stepHeader}>
              <View style={styles.stepIndicatorRow}>
                <View style={[styles.stepDot, styles.stepDotActive]} />
                <View style={[styles.stepLine, styles.stepLineInactive]} />
                <View style={styles.stepDot} />
              </View>
              <Text style={styles.stepTitle}>Step 1 of 2</Text>
              <Text style={styles.pageTitle}>Select Doctor</Text>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <Text style={styles.pageSubtitle}>Which doctor's order are you placing?</Text>
                <TouchableOpacity onPress={() => setAddDoctorVisible(true)} style={styles.addDoctorBtn}>
                  <Ionicons name="add" size={16} color="#064E3B" />
                  <Text style={styles.addDoctorBtnText}>New</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>

          <View style={styles.searchBox}>
            <Ionicons name="search" size={18} color="#9CA3AF" style={{ marginRight: 8 }} />
            <TextInput
              style={styles.searchInput}
              placeholder="Search by name or phone..."
              value={doctorSearch}
              onChangeText={setDoctorSearch}
              placeholderTextColor="#D1D5DB"
            />
            {doctorSearch.length > 0 && (
              <TouchableOpacity onPress={() => setDoctorSearch('')}>
                <Ionicons name="close-circle" size={18} color="#D1D5DB" />
              </TouchableOpacity>
            )}
          </View>

          {doctorsLoading ? (
            <View style={styles.center}>
              <ActivityIndicator size="large" color="#6366F1" style={{ marginBottom: 10 }} />
              <Text style={styles.loadingText}>Loading doctors...</Text>
            </View>
          ) : filteredDoctors.length === 0 ? (
            <View style={styles.center}>
              <Ionicons name="person-outline" size={48} color="#E5E7EB" />
              <Text style={styles.emptyText}>No doctors found</Text>
            </View>
          ) : (
            <FlatList
              data={filteredDoctors}
              keyExtractor={item => item.id.toString()}
              contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
              renderItem={({ item }) => (
                <TouchableOpacity style={styles.doctorCard} onPress={() => handleSelectDoctor(item)}>
                  <View style={styles.doctorAvatar}>
                    <Text style={styles.doctorAvatarText}>{item.name.charAt(0).toUpperCase()}</Text>
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.doctorName}>Dr. {item.name}</Text>
                    <Text style={styles.doctorPhone}>{item.phone}</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={18} color="#D1D5DB" />
                </TouchableOpacity>
              )}
            />
          )}

          {/* Add Doctor Modal */}
          {addDoctorVisible && (
            <View style={styles.modalOverlay}>
              <View style={styles.modalContent}>
                <Text style={styles.modalTitle}>Add New Doctor</Text>

                <Text style={styles.modalLabel}>Doctor Name</Text>
                <TextInput
                  style={styles.modalInput}
                  placeholder="e.g. Rahul Sharma"
                  value={newDoctorName}
                  onChangeText={setNewDoctorName}
                />

                <Text style={styles.modalLabel}>Phone Number</Text>
                <TextInput
                  style={styles.modalInput}
                  placeholder="e.g. 9876543210"
                  keyboardType="phone-pad"
                  value={newDoctorPhone}
                  onChangeText={setNewDoctorPhone}
                />

                <View style={styles.modalActions}>
                  <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setAddDoctorVisible(false)}>
                    <Text style={styles.modalCancelText}>Cancel</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.modalSaveBtn} onPress={handleAddDoctor} disabled={isAddingDoctor}>
                    {isAddingDoctor ? <ActivityIndicator color="#fff" /> : <Text style={styles.modalSaveText}>Save</Text>}
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          )}

        </SafeAreaView>
      </View>
    );
  }

  // ───── STEP 2: Product Selection ─────
  return (
    <View style={{ flex: 1, backgroundColor: '#064E3B' }}>
      <SafeAreaView style={{ flex: 1, backgroundColor: '#F0FDF4' }} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor="#064E3B" />

        <View style={styles.stepHeaderWrapper}>
          <View style={styles.stepHeader}>
            <View style={styles.stepIndicatorRow}>
              <View style={[styles.stepDot, styles.stepDotDone]}>
                <Ionicons name="checkmark" size={12} color="#fff" />
              </View>
              <View style={[styles.stepLine, styles.stepLineActive]} />
              <View style={[styles.stepDot, styles.stepDotActive]} />
            </View>
            <View style={styles.selectedDoctorBanner}>
              <Ionicons name="person-circle-outline" size={18} color="#064E3B" style={{ marginRight: 6 }} />
              <Text style={styles.selectedDoctorText}>Dr. {selectedDoctor?.name}</Text>
              <TouchableOpacity onPress={() => { setStep(1); setCart(new Map()); }} style={{ marginLeft: 'auto' }}>
                <Text style={styles.changeLink}>Change</Text>
              </TouchableOpacity>
            </View>
            <Text style={styles.pageTitle}>Select Products</Text>
            <Text style={styles.pageSubtitle}>Add medicines to the order</Text>
          </View>
        </View>

        <View style={styles.searchBox}>
          <Ionicons name="search" size={18} color="#9CA3AF" style={{ marginRight: 8 }} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search medicines..."
            value={productSearch}
            onChangeText={setProductSearch}
            placeholderTextColor="#D1D5DB"
          />
        </View>

        {productsLoading ? (
          <View style={styles.center}>
            <ActivityIndicator size="large" color="#6366F1" />
          </View>
        ) : (
          <FlatList
            data={filteredProducts}
            keyExtractor={item => item.id.toString()}
            contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: cartCount > 0 ? 200 : 40 }}
            renderItem={({ item }) => {
              const inCart = cart.get(item.id);
              return (
                <View style={styles.productCard}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.productName}>{item.name}</Text>
                    {item.description ? <Text style={styles.productDesc} numberOfLines={1}>{item.description}</Text> : null}
                    <Text style={styles.productPrice}>₹{parseFloat(item.price).toFixed(2)}</Text>
                    <Text style={{ fontSize: 11, color: item.stock_quantity > 0 ? '#059669' : '#DC2626', marginTop: 4, fontWeight: '600' }}>
                      {item.stock_quantity > 0 ? `${item.stock_quantity} in stock` : 'Out of Stock'}
                    </Text>
                  </View>
                  <View style={styles.qtyControls}>
                    {item.stock_quantity <= 0 ? (
                      <View style={{ paddingHorizontal: 10, paddingVertical: 6, backgroundColor: '#FEF2F2', borderRadius: 8, borderWidth: 1, borderColor: '#FCA5A5' }}>
                        <Text style={{ color: '#DC2626', fontSize: 10, fontWeight: '800' }}>UNAVAILABLE</Text>
                      </View>
                    ) : inCart ? (
                      <>
                        <TouchableOpacity style={[styles.qtyBtn, { marginRight: 8 }]} onPress={() => updateCart(item, -1)}>
                          <Ionicons name="remove" size={16} color="#059669" />
                        </TouchableOpacity>
                        <Text style={[styles.qtyText, { marginRight: 8 }]}>{inCart.quantity}</Text>
                        <TouchableOpacity
                          style={[styles.qtyBtn, styles.qtyBtnFilled, inCart.quantity >= item.stock_quantity && { opacity: 0.4 }]}
                          onPress={() => updateCart(item, 1)}
                        >
                          <Ionicons name="add" size={16} color="#fff" />
                        </TouchableOpacity>
                      </>
                    ) : (
                      <TouchableOpacity style={[styles.qtyBtn, styles.qtyBtnFilled]} onPress={() => updateCart(item, 1)}>
                        <Ionicons name="add" size={16} color="#fff" />
                      </TouchableOpacity>
                    )}
                  </View>
                </View>
              );
            }}
          />
        )}

        {/* Cart Footer */}
        {cartCount > 0 && (
          <View style={styles.cartFooter}>
            <View style={styles.notesRow}>
              <Ionicons name="create-outline" size={16} color="#9CA3AF" style={{ marginRight: 6 }} />
              <TextInput
                style={styles.notesInput}
                placeholder="Add a note (optional)..."
                value={notes}
                onChangeText={setNotes}
                placeholderTextColor="#D1D5DB"
              />
            </View>
            <View style={styles.cartSummaryRow}>
              <View>
                <Text style={styles.cartItemCount}>{cartCount} item{cartCount !== 1 ? 's' : ''} selected</Text>
                <Text style={styles.cartTotal}>₹{cartTotal.toFixed(2)}</Text>
              </View>
              <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={submitting}>
                {submitting ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <>
                    <Text style={styles.submitBtnText}>Place Order</Text>
                    <Ionicons name="send" size={16} color="#fff" style={{ marginLeft: 8 }} />
                  </>
                )}
              </TouchableOpacity>
            </View>
          </View>
        )}
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F0FDF4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { color: '#9CA3AF', fontSize: 14 },

  stepHeaderWrapper: {
    backgroundColor: '#064E3B',
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 0,
    zIndex: 10,
    marginTop: -50,
    paddingTop: 50 + 16,
    marginBottom: 16,
  },
  stepHeader: {},
  stepIndicatorRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  stepDot: { width: 20, height: 20, borderRadius: 10, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  stepDotActive: { backgroundColor: '#10B981' },
  stepDotDone: { backgroundColor: '#059669' },
  stepLine: { flex: 1, height: 3, marginHorizontal: 4, borderRadius: 2 },
  stepLineInactive: { backgroundColor: 'rgba(255,255,255,0.2)' },
  stepLineActive: { backgroundColor: '#10B981' },
  stepTitle: { fontSize: 12, color: '#A7F3D0', fontWeight: '600', marginBottom: 4 },
  pageTitle: { fontSize: 24, fontWeight: '800', color: '#FFFFFF' },
  pageSubtitle: { fontSize: 14, color: '#A7F3D0', marginTop: 4 },
  selectedDoctorBanner: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#D1FAE5', borderRadius: 10, padding: 10, marginBottom: 8 },
  selectedDoctorText: { fontSize: 14, fontWeight: '700', color: '#064E3B' },
  changeLink: { fontSize: 13, color: '#059669', fontWeight: '600' },

  searchBox: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, marginHorizontal: 16, marginBottom: 12, paddingHorizontal: 14, paddingVertical: 12, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 8, elevation: 2 },
  searchInput: { flex: 1, fontSize: 15, color: '#1F2937' },

  doctorCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 16, padding: 16, marginBottom: 10, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 8, elevation: 2, marginHorizontal: 16 },
  doctorAvatar: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#D1FAE5', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  doctorAvatarText: { fontSize: 18, fontWeight: '800', color: '#059669' },
  doctorName: { fontSize: 15, fontWeight: '700', color: '#1F2937' },
  doctorPhone: { fontSize: 13, color: '#9CA3AF', marginTop: 2 },
  emptyText: { fontSize: 15, color: '#9CA3AF', marginTop: 8 },

  productCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 14, marginBottom: 10, shadowColor: '#000', shadowOpacity: 0.04, shadowRadius: 6, elevation: 1 },
  productName: { fontSize: 14, fontWeight: '700', color: '#1F2937' },
  productDesc: { fontSize: 12, color: '#9CA3AF', marginTop: 2 },
  productPrice: { fontSize: 14, fontWeight: '700', color: '#059669', marginTop: 4 },
  qtyControls: { flexDirection: 'row', alignItems: 'center' },
  qtyBtn: { width: 32, height: 32, borderRadius: 10, borderWidth: 1.5, borderColor: '#059669', justifyContent: 'center', alignItems: 'center' },
  qtyBtnFilled: { backgroundColor: '#059669', borderColor: '#059669' },
  qtyText: { fontSize: 15, fontWeight: '800', color: '#059669', minWidth: 20, textAlign: 'center' },

  cartFooter: { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: '#fff', borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: 20, shadowColor: '#000', shadowOpacity: 0.12, shadowRadius: 16, shadowOffset: { width: 0, height: -4 }, elevation: 10 },
  notesRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#F9FAFB', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 10, marginBottom: 14 },
  notesInput: { flex: 1, fontSize: 14, color: '#1F2937' },
  cartSummaryRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  cartItemCount: { fontSize: 12, color: '#9CA3AF', fontWeight: '600' },
  cartTotal: { fontSize: 22, fontWeight: '800', color: '#1F2937', marginTop: 2 },
  submitBtn: { flexDirection: 'row', backgroundColor: '#059669', paddingVertical: 14, paddingHorizontal: 22, borderRadius: 14, alignItems: 'center', shadowColor: '#059669', shadowOpacity: 0.35, shadowRadius: 10, elevation: 6 },
  submitBtnText: { color: '#fff', fontSize: 15, fontWeight: '700' },

  addDoctorBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#D1FAE5', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 12, marginTop: 4 },
  addDoctorBtnText: { color: '#064E3B', fontSize: 13, fontWeight: '700', marginLeft: 4 },

  modalOverlay: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center', zIndex: 100 },
  modalContent: { backgroundColor: '#fff', width: '85%', borderRadius: 16, padding: 20 },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1F2937', marginBottom: 16 },
  modalLabel: { fontSize: 13, fontWeight: '600', color: '#4B5563', marginBottom: 6 },
  modalInput: { backgroundColor: '#F3F4F6', borderRadius: 8, padding: 12, fontSize: 15, marginBottom: 16, color: '#1F2937' },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: 8 },
  modalCancelBtn: { paddingHorizontal: 16, paddingVertical: 10, marginRight: 8 },
  modalCancelText: { color: '#6B7280', fontSize: 14, fontWeight: '600' },
  modalSaveBtn: { backgroundColor: '#6366F1', paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8, minWidth: 80, alignItems: 'center' },
  modalSaveText: { color: '#fff', fontSize: 14, fontWeight: '600' },
});
